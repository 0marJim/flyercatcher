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
        
        try {
            $stmt = $db->prepare("INSERT INTO events (title, description, location, event_date, category, image_gradient, posted_by) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $input['title'],
                $description,
                $input['location'],
                $input['event_date'],
                $category,
                $imageGradient,
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