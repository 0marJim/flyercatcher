<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = new PDO('sqlite:events.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Route handling
switch ($method) {
    case 'GET':
        handleGetRequest($db, $path);
        break;
    case 'POST':
        handlePostRequest($db, $path);
        break;
    case 'PUT':
        handlePutRequest($db, $path);
        break;
    case 'DELETE':
        handleDeleteRequest($db, $path);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGetRequest($db, $path) {
    if ($path === 'events' || $path === '') {
        // Get all events with optional category filter
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        
        $sql = "SELECT * FROM events ORDER BY created_at DESC";
        $params = [];
        
        if ($category && $category !== 'all') {
            $sql = "SELECT * FROM events WHERE category = ? ORDER BY created_at DESC";
            $params = [$category];
        }
        // could be more efficient?????? 
        // Issue: In handleGetRequest, the loop that formats formatted_date and posted_date could be optimized by performing date formatting in SQL for large datasets.
        // Recommendation: Use SQL to format dates if possible (though SQLite has limited date formatting). Alternatively, ensure the dataset size is manageable or implement pagination:
        // php$sql = "SELECT * FROM events WHERE category = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        // $params = [$category, $limit, $offset];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates and add relative time
        foreach ($events as &$event) {
            $event['formatted_date'] = formatEventDate($event['event_date']);
            $event['posted_date'] = getRelativeTime($event['created_at']);
        }
        
        echo json_encode($events);
        
    } elseif (preg_match('/^events\/(\d+)$/', $path, $matches)) {
        // Get single event
        $eventId = $matches[1];
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            $event['formatted_date'] = formatEventDate($event['event_date']);
            $event['posted_date'] = getRelativeTime($event['created_at']);
            echo json_encode($event);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePostRequest($db, $path) {
    if ($path === 'events' || $path === '') {
        // Check if this is a file upload request
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            handleImageUpload($db);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['title', 'location', 'event_date'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Set defaults
        $description = isset($input['description']) ? $input['description'] : '';
        $category = isset($input['category']) ? $input['category'] : 'other';
        $postedBy = isset($input['posted_by']) ? $input['posted_by'] : 'Anonymous';
        $imageGradient = isset($input['image_gradient']) ? $input['image_gradient'] : getRandomGradient();
        $imageUrl = isset($input['image_url']) ? $input['image_url'] : '';
        
        try {
            $stmt = $db->prepare("INSERT INTO events (title, description, location, event_date, category, image_gradient, image_url, posted_by) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $input['title'],
                $description,
                $input['location'],
                $input['event_date'],
                $category,
                $imageGradient,
                $imageUrl,
                $postedBy
            ]);
            
            $eventId = $db->lastInsertId();
            
            // Return the created event
            $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $event['formatted_date'] = formatEventDate($event['event_date']);
            $event['posted_date'] = getRelativeTime($event['created_at']);
            
            http_response_code(201);
            echo json_encode($event);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create event']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleImageUpload($db) {
    try {
        // Validate file upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid image uploaded']);
            return;
        }
        // Add server-side file validation (e.g., using getimagesize() to verify image content) and sanitize filenames to prevent directory traversal attacks.
        if (!getimagesize($file['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid image file']);
            return;
        }
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename); // Sanitize filename
        
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed.']);
            return;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            http_response_code(400);
            echo json_encode(['error' => 'File too large. Maximum size is 10MB.']);
            return;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('event_', true) . '.' . $extension;
        define('UPLOAD_DIR', __DIR__ . '/uploads/');
        $uploadPath = UPLOAD_DIR . $filename;
        // $uploadPath = 'uploads/' . $filename;
        $fullPath = __DIR__ . '/' . $uploadPath;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save uploaded file']);
            return;
        }
        
        // Get form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $eventDate = $_POST['event_date'] ?? '';
        $category = $_POST['category'] ?? 'other';
        $postedBy = $_POST['posted_by'] ?? 'Anonymous';
        
        // Validate required fields
        if (empty($title) || empty($location) || empty($eventDate)) {
            unlink($fullPath); // Clean up uploaded file
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        // Insert into database
        $stmt = $db->prepare("INSERT INTO events (title, description, location, event_date, category, image_gradient, image_url, posted_by) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $title,
            $description,
            $location,
            $eventDate,
            $category,
            '', // No gradient when we have an image
            $uploadPath,
            $postedBy
        ]);
        
        $eventId = $db->lastInsertId();
        
        // Return the created event
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $event['formatted_date'] = formatEventDate($event['event_date']);
        $event['posted_date'] = getRelativeTime($event['created_at']);
        
        http_response_code(201);
        echo json_encode($event);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process image upload']);
    }
}

function handlePutRequest($db, $path) {
    if (preg_match('/^events\/(\d+)$/', $path, $matches)) {
        $eventId = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Check if event exists
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingEvent) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }
        
        // Build update query dynamically
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'location', 'event_date', 'category'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $eventId;
        
        try {
            $sql = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Return updated event
            $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $event['formatted_date'] = formatEventDate($event['event_date']);
            $event['posted_date'] = getRelativeTime($event['created_at']);
            
            echo json_encode($event);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update event']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($db, $path) {
    if (preg_match('/^events\/(\d+)$/', $path, $matches)) {
        $eventId = $matches[1];
        
        try {
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $result = $stmt->execute([$eventId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Event not found']);
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete event']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function formatEventDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('D, M j, g:i A');
}

function getRelativeTime($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days > 7) {
        return $diff->days . ' days ago';
    } elseif ($diff->days > 0) {
        return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}

function getRandomGradient() {
    $gradients = [
        "linear-gradient(45deg, #667eea, #764ba2)",
        "linear-gradient(45deg, #f093fb, #f5576c)",
        "linear-gradient(45deg, #4facfe, #00f2fe)",
        "linear-gradient(45deg, #43e97b, #38f9d7)",
        "linear-gradient(45deg, #fa709a, #fee140)",
        "linear-gradient(45deg, #a8edea, #fed6e3)",
        "linear-gradient(45deg, #ffecd2, #fcb69f)",
        "linear-gradient(45deg, #c3cfe2, #c3cfe2)"
    ];
    return $gradients[array_rand($gradients)];
}
?>