<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// Safely extract PATH_INFO even if Apache passes it via REQUEST_URI
$pathInfo = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/');
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptName = $_SERVER['SCRIPT_NAME'];
    if (strpos($uriPath, $scriptName) === 0) {
        $pathInfo = trim(substr($uriPath, strlen($scriptName)), '/');
    }
}

$request = $pathInfo !== '' ? explode('/', $pathInfo) : [];
$input = json_decode(file_get_contents('php://input'), true);

// Database connection (switch credentials when deploying to Mercury)
// $conn = mysqli_connect('feenix-mariadb.swin.edu.au', 's106200220', '280904', 's106200220_db');
$conn = mysqli_connect('localhost', 'root', '', 'nexus');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Whitelist allowed tables
$allowedTables = ['game', 'cart', 'developer', 'favourite', 'payment', 'user'];

// First segment is table name; default to 'game' if omitted
$firstSegment = isset($request[0]) ? preg_replace('/[^a-z0-9_]+/i', '', $request[0]) : '';

if (in_array($firstSegment, $allowedTables, true)) {
    $table = array_shift($request);
} else {
    $table = 'game';
}

// Auto-generate next sequential ID (U0001 / D0001) on POST if missing or empty
if ($method === 'POST' && ($table === 'user' || $table === 'developer')) {
    if (!is_array($input)) {
        $input = [];
    }

    $idCol = ($table === 'developer') ? 'developer_id' : 'user_id';
    $prefix = ($table === 'developer') ? 'D' : 'U';

    if (empty($input[$idCol])) {
        $maxRes = mysqli_query(
            $conn,
            "SELECT MAX(CAST(SUBSTRING(`$idCol`, 2) AS UNSIGNED)) AS max_num FROM `$table` WHERE `$idCol` LIKE '{$prefix}%'"
        );
        $maxRow = $maxRes ? mysqli_fetch_assoc($maxRes) : null;
        $nextNum = ($maxRow && $maxRow['max_num'] !== null) ? ((int)$maxRow['max_num'] + 1) : 1;

        $input[$idCol] = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    }

    if ($table === 'user' && !isset($input['avatar'])) {
        $input['avatar'] = '';
    }
}

// Remaining segments: optional search field and value
$fld = preg_replace('/[^a-z0-9_]+/i', '', (string) array_shift($request));
$key = array_shift($request);
$escapedKey = $key !== null ? mysqli_real_escape_string($conn, (string) urldecode($key)) : null;

// Prepare SET clause for PUT / POST
$set = '';
if (is_array($input) && count($input) > 0) {
    $columns = preg_replace('/[^a-z0-9_]+/i', '', array_keys($input));
    $values = array_map(function ($value) use ($conn) {
        if ($value === null) return null;
        return mysqli_real_escape_string($conn, (string) $value);
    }, array_values($input));

    $parts = [];
    for ($i = 0; $i < count($columns); $i++) {
        if ($columns[$i] === '') continue;
        $valSql = $values[$i] === null ? 'NULL' : '"' . $values[$i] . '"';
        $parts[] = '`' . $columns[$i] . '`=' . $valSql;
    }
    $set = implode(',', $parts);
}

// Build SQL query
switch ($method) {
    case 'GET':
        $where = ($fld !== '' && $escapedKey !== null) ? " WHERE `$fld`='$escapedKey'" : '';
        $sql = "SELECT * FROM `$table`" . $where;
        break;
    case 'PUT':
        $where = ($fld !== '' && $escapedKey !== null) ? " WHERE `$fld`='$escapedKey'" : ' WHERE 0=1';
        $sql = "UPDATE `$table` SET $set" . $where;
        break;
    case 'POST':
        $sql = "INSERT INTO `$table` SET $set";
        break;
    case 'DELETE':
        $where = ($fld !== '' && $escapedKey !== null) ? " WHERE `$fld`='$escapedKey'" : ' WHERE 0=1';
        $sql = "DELETE FROM `$table`" . $where;
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        mysqli_close($conn);
        exit;
}

$result = mysqli_query($conn, $sql);

if ($result) {
    if ($method === 'GET') {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Safely encode BLOB avatar so json_encode never fails on binary bytes
            if (isset($row['avatar']) && $row['avatar'] !== '') {
                $row['avatar'] = base64_encode($row['avatar']);
            }
            $rows[] = $row;
        }
        echo json_encode($rows);
    } elseif ($method === 'POST') {
        $response = ['insert_id' => mysqli_insert_id($conn)];
        if ($table === 'user' && isset($input['user_id'])) {
            $response['user_id'] = $input['user_id'];
        } elseif ($table === 'developer' && isset($input['developer_id'])) {
            $response['developer_id'] = $input['developer_id'];
        }
        echo json_encode($response);
    } else {
        echo json_encode(['affected_rows' => mysqli_affected_rows($conn)]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>