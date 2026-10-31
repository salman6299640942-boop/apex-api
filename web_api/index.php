<?php
require_once __DIR__ . '/config.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    $input = $_REQUEST;
}

$action = $input['action'] ?? $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'login':
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $owner = $input['owner'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['ok' => false, 'error' => 'bad_login']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM accounts WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['ok' => false, 'error' => 'bad_login']);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'email' => $user['email'],
            'owner' => $owner,
            'user_id' => $user['id']
        ]);
        break;

    case 'register':
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $owner = $input['owner'] ?? bin2hex(random_bytes(16));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'bad_email']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['ok' => false, 'error' => 'bad_password']);
            exit;
        }

        $stmt = $db->prepare("SELECT id FROM accounts WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'email_taken']);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'email' => $email,
            'owner' => $owner
        ]);
        break;

    case 'get_chars':
        $email = strtolower(trim($input['email'] ?? ''));
        $stmt = $db->prepare("SELECT id, name, level, money, skin, admin FROM accounts WHERE email = ?");
        $stmt->execute([$email]);
        $chars = $stmt->fetchAll();

        // Format for Apex Launcher CEF
        $resultChars = [];
        foreach ($chars as $c) {
            $resultChars[] = [
                'id' => $c['id'],
                'nickname' => $c['name'],
                'name' => $c['name'],
                'level' => intval($c['level']),
                'money' => intval($c['money']),
                'skin' => intval($c['skin']),
                'admin' => intval($c['admin'])
            ];
        }

        echo json_encode([
            'ok' => true,
            'characters' => $resultChars
        ]);
        break;

    case 'create_char':
        $email = strtolower(trim($input['email'] ?? ''));
        $fn = trim($input['fn'] ?? $input['first_name'] ?? '');
        $ln = trim($input['ln'] ?? $input['last_name'] ?? '');
        $gender = ($input['gender'] ?? 'M') === 'F' ? 2 : 1;

        $nickname = trim($input['nickname'] ?? '');
        if (!$nickname && $fn && $ln) {
            $nickname = ucfirst(strtolower($fn)) . '_' . ucfirst(strtolower($ln));
        }

        if (!$nickname || strlen($nickname) < 3) {
            echo json_encode(['ok' => false, 'error' => 'invalid_name']);
            exit;
        }

        $stmt = $db->prepare("SELECT id FROM accounts WHERE name = ? LIMIT 1");
        $stmt->execute([$nickname]);
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'taken']);
            exit;
        }

        $skin = ($gender === 1) ? 1 : 12;
        $insert = $db->prepare("INSERT INTO accounts (name, password, salt, email, level, money, bank, skin, sex, admin, AdminPass) VALUES (?, ?, ?, ?, 1, 50000, 100000, ?, ?, 0, 1234)");
        $insert->execute([$nickname, 'apex_pass', 'salt', $email, $skin, $gender]);

        echo json_encode([
            'ok' => true,
            'nickname' => $nickname,
            'skin' => $skin
        ]);
        break;

    case 'account':
        $owner = $input['owner'] ?? '';
        echo json_encode([
            'ok' => true,
            'account_id' => rand(1000, 9999),
            'vc_balance' => 1000,
            'ac_balance' => 1000
        ]);
        break;

    case 'buy_ac':
    case 'buy_vc':
        echo json_encode([
            'ok' => true,
            'url' => 'https://apexrp.com/donate'
        ]);
        break;

    default:
        echo json_encode(['ok' => true, 'status' => 'Black Russia Apex API Active']);
        break;
}
