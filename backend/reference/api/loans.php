<?php
// backend/api/loans.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/helpers.php';
cors();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? '';

// GET /api/loans.php            → list all loans
// GET /api/loans.php?id=5       → loan + schedule
// GET /api/loans.php?action=pipeline → pipeline grouped by status
// POST /api/loans.php           → create loan (saves schedule too)
// PUT  /api/loans.php?id=5      → update loan status / details
// POST /api/loans.php?action=calc → compute schedule without saving

if ($method === 'GET') {
    if ($action === 'pipeline') {
        $stmt = $db->query("
            SELECT l.*, m.first_name, m.last_name, m.member_no,
                   lt.label as loan_type_label
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN loan_types lt ON l.loan_type_id = lt.id
            ORDER BY l.updated_at DESC
        ");
        $rows = $stmt->fetchAll();
        $pipeline = ['DRAFT'=>[], 'PENDING'=>[], 'APPROVED'=>[], 'ACTIVE'=>[], 'CLOSED'=>[], 'REJECTED'=>[]];
        foreach ($rows as $r) { $pipeline[$r['status']][] = $r; }
        json_ok($pipeline);
    }

    if ($id) {
        $stmt = $db->prepare("
            SELECT l.*, m.first_name, m.last_name, m.member_no, m.contact,
                   m.email, m.address, m.company, m.position, m.status as emp_status,
                   m.supervisor, m.monthly_salary,
                   lt.label as loan_type_label, lt.code as loan_type_code,
                   cm1.first_name as co1_first, cm1.last_name as co1_last,
                   cm2.first_name as co2_first, cm2.last_name as co2_last
            FROM loans l
            JOIN members m  ON l.member_id = m.id
            JOIN loan_types lt ON l.loan_type_id = lt.id
            LEFT JOIN members cm1 ON l.co_maker_1_id = cm1.id
            LEFT JOIN members cm2 ON l.co_maker_2_id = cm2.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $loan = $stmt->fetch();
        if (!$loan) json_err('Loan not found', 404);

        $sched = $db->prepare("SELECT * FROM amortization_schedule WHERE loan_id = ? ORDER BY period_no");
        $sched->execute([$id]);
        $loan['schedule'] = $sched->fetchAll();
        json_ok($loan);
    }

    // list with optional filters
    $memberId = $_GET['member_id'] ?? null;
    $status   = $_GET['status'] ?? null;
    $where = []; $params = [];
    if ($memberId) { $where[] = 'l.member_id = ?'; $params[] = $memberId; }
    if ($status)   { $where[] = 'l.status = ?';    $params[] = $status; }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT l.*, m.first_name, m.last_name, m.member_no, lt.label as loan_type_label
        FROM loans l
        JOIN members m ON l.member_id = m.id
        JOIN loan_types lt ON l.loan_type_id = lt.id
        $whereSql
        ORDER BY l.created_at DESC
        LIMIT 500
    ");
    $stmt->execute($params);
    json_ok($stmt->fetchAll());
}

if ($method === 'POST') {
    $d = body();

    // ── Calc-only endpoint ─────────────────────────────────
    if ($action === 'calc') {
        $required = ['amount','term_months','frequency','annual_rate'];
        foreach ($required as $f) { if (!isset($d[$f])) json_err("Missing: $f"); }
        json_ok(computeSchedule((float)$d['amount'], (int)$d['term_months'], $d['frequency'], (float)$d['annual_rate']));
    }

    // ── Create loan ────────────────────────────────────────
    $required = ['member_id','loan_type_id','amount','term_months','frequency'];
    foreach ($required as $f) { if (empty($d[$f])) json_err("Field '$f' is required"); }

    // fetch loan type rate
    $ltStmt = $db->prepare("SELECT * FROM loan_types WHERE id = ?");
    $ltStmt->execute([$d['loan_type_id']]);
    $lt = $ltStmt->fetch();
    if (!$lt) json_err('Invalid loan type');

    $rate = (float)($d['annual_rate'] ?? $lt['annual_rate']);
    $calc = computeSchedule((float)$d['amount'], (int)$d['term_months'], $d['frequency'], $rate);

    $loanNo       = generateLoanNo($db);
    $appDate      = $d['application_date'] ?? date('Y-m-d');
    $firstDueDate = $d['first_due_date'] ?? null;
    $endDate      = null;

    if ($firstDueDate) {
        $dates = generateDueDates($firstDueDate, $calc['n_periods'], $d['frequency']);
        $endDate = end($dates);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO loans
              (loan_no, member_id, loan_type_id, amount, term_months, frequency, annual_rate,
               purpose, co_maker_1_id, co_maker_2_id, status,
               total_payment, total_interest, n_periods, first_payment_amt, last_payment_amt,
               application_date, first_due_date, end_date, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $loanNo, $d['member_id'], $d['loan_type_id'],
            $d['amount'], $d['term_months'], $d['frequency'], $rate,
            $d['purpose'] ?? null, $d['co_maker_1_id'] ?? null, $d['co_maker_2_id'] ?? null,
            $d['status'] ?? 'DRAFT',
            $calc['total_payment'], $calc['total_interest'], $calc['n_periods'],
            $calc['first_payment'], $calc['last_payment'],
            $appDate, $firstDueDate, $endDate,
            $d['notes'] ?? null, $d['created_by'] ?? 1,
        ]);
        $loanId = $db->lastInsertId();

        // insert schedule
        $dueDates = $firstDueDate ? generateDueDates($firstDueDate, $calc['n_periods'], $d['frequency']) : [];
        $ins = $db->prepare("
            INSERT INTO amortization_schedule (loan_id, period_no, due_date, principal, interest, amount_due, balance)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($calc['schedule'] as $i => $row) {
            $ins->execute([
                $loanId, $row['period'],
                $dueDates[$i] ?? null,
                $row['principal'], $row['interest'], $row['payment'], $row['balance'],
            ]);
        }

        $db->commit();
        json_ok(['id' => $loanId, 'loan_no' => $loanNo, 'calc' => $calc], 201);
    } catch (\Exception $e) {
        $db->rollBack();
        json_err('Failed to save loan: ' . $e->getMessage(), 500);
    }
}

if ($method === 'PUT' && $id) {
    $d = body();
    $allowed = ['status','purpose','co_maker_1_id','co_maker_2_id','approved_by_hr',
                'approved_by_coop','approval_date','signed_form_url','notes',
                'first_due_date','application_date'];
    $sets = []; $params = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $d)) { $sets[] = "$f = ?"; $params[] = $d[$f]; }
    }
    if (!$sets) json_err('Nothing to update');
    $params[] = $id;
    $db->prepare("UPDATE loans SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    json_ok(['updated' => true]);
}

json_err('Method not allowed', 405);
