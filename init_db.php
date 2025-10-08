<?php
// Initialize SQLite database for FlyerCatcher events
try {
    // Create database file with proper permissions
    $db = new PDO('sqlite:events.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create events table
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        location VARCHAR(255) NOT NULL,
        event_date DATETIME NOT NULL,
        category VARCHAR(50) NOT NULL,
        image_gradient VARCHAR(100),
        image_url VARCHAR(255),
        posted_by VARCHAR(100) DEFAULT 'Anonymous',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    
    // Insert sample events if table is empty
    $count = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    
    if ($count == 0) {
        $sampleEvents = [
            [
                'title' => 'Summer Jazz Festival',
                'description' => 'An evening of smooth jazz with local and touring artists',
                'location' => 'Riverside Park Amphitheater',
                'event_date' => '2024-07-15 19:00:00',
                'category' => 'music',
                'image_gradient' => 'linear-gradient(45deg, #667eea, #764ba2)',
                'posted_by' => 'EventPro'
            ],
            [
                'title' => 'Food Truck Friday',
                'description' => 'Over 20 food trucks serving diverse cuisines',
                'location' => 'Downtown Square',
                'event_date' => '2024-07-12 17:00:00',
                'category' => 'food',
                'image_gradient' => 'linear-gradient(45deg, #f093fb, #f5576c)',
                'posted_by' => 'CityEvents'
            ],
            [
                'title' => 'Local Art Exhibition',
                'description' => 'Featuring works by emerging local artists',
                'location' => 'Community Arts Center',
                'event_date' => '2024-07-20 10:00:00',
                'category' => 'art',
                'image_gradient' => 'linear-gradient(45deg, #4facfe, #00f2fe)',
                'posted_by' => 'ArtLover'
            ],
            [
                'title' => 'Morning Yoga in the Park',
                'description' => 'Free community yoga session for all levels',
                'location' => 'Central Park Pavilion',
                'event_date' => '2024-07-13 08:00:00',
                'category' => 'community',
                'image_gradient' => 'linear-gradient(45deg, #43e97b, #38f9d7)',
                'posted_by' => 'YogaCommunity'
            ],
            [
                'title' => 'Basketball Tournament',
                'description' => '3v3 street basketball tournament with prizes',
                'location' => 'Community Recreation Center',
                'event_date' => '2024-07-16 14:00:00',
                'category' => 'sports',
                'image_gradient' => 'linear-gradient(45deg, #fa709a, #fee140)',
                'posted_by' => 'SportsClub'
            ],
            [
                'title' => 'Poetry Open Mic Night',
                'description' => 'Share your poetry or enjoy performances by others',
                'location' => 'The Coffee House',
                'event_date' => '2024-07-18 19:30:00',
                'category' => 'art',
                'image_gradient' => 'linear-gradient(45deg, #a8edea, #fed6e3)',
                'posted_by' => 'PoetryGroup'
            ]
        ];
        
        // Fix: Exclude image_url from INSERT since it's not provided
        $stmt = $db->prepare("INSERT INTO events (title, description, location, event_date, category, image_gradient, posted_by) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sampleEvents as $event) {
            $stmt->execute([
                $event['title'],
                $event['description'],
                $event['location'],
                $event['event_date'],
                $event['category'],
                $event['image_gradient'],
                $event['posted_by']
            ]);
        }
        
        echo "Database initialized successfully with sample events!\n";
    } else {
        echo "Database already exists with $count events.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>