<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$isAdmin = isAdmin();

// Fetch projects with error handling
try {
    $stmt = $db->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching projects.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isAdmin ? 'Manage Projects' : 'Our Portfolio' ?> | JT Kitchen Equipment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="portfolio.css">
    <style>
        /* Enhanced Styles */
        :root {
            --primary-color: #8a0000;
            --secondary-color: #d32f2f;
            --light-color: #f5f5f5;
            --dark-color: #333;
            --success-color: #4caf50;
            --error-color: #f44336;
            --shadow: 0 4px 8px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: var(--dark-color);
            margin: 0;
            padding: 0;
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 30px;
            margin-left: 70px;
            box-shadow: var(--shadow);
            height: auto;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .header-content h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 600;
        }
        
        .header-content p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        #currentDateTime {
            font-size: 14px;
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .profile-menu i {
            font-size: 32px;
            color: white;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .profile-menu i:hover {
            color: #ddd;
            transform: scale(1.1);
        }
        
        .dropdown {
            display: none;
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            min-width: 150px;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
            z-index: 1000;
        }
        
        .dropdown.show {
            display: block;
        }
        
        .dropdown li {
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }
        
        .dropdown li:hover {
            background: #f0f0f0;
        }
        
        .dropdown li i {
            font-size: 16px;
            color: var(--primary-color);
        }
        
        .dropdown li span {
            font-size: 14px;
            color: var(--dark-color);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 70px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            color: var(--primary-color);
            margin: 0;
        }
        
        .projects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .project-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .project-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .project-card-content {
            padding: 20px;
        }
        
        .project-card h3 {
            margin: 0 0 10px;
            color: var(--primary-color);
            font-size: 20px;
        }
        
        .project-card p {
            margin: 0 0 15px;
            color: #666;
            line-height: 1.5;
        }
        
        .project-actions {
            display: flex;
            gap: 10px;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .project-actions button {
            background: rgba(255,255,255,0.9);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }
        
        .project-actions button:hover {
            background: white;
            transform: scale(1.1);
        }
        
        .project-actions button i {
            color: var(--primary-color);
            font-size: 16px;
        }
        
        /* Buttons */
        .add-button, .status-tag {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .add-button:hover, .status-tag:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .add-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            justify-content: center;
            font-size: 24px;
            z-index: 100;
            box-shadow: 0 4px 15px rgba(138, 0, 0, 0.3);
        }
        
        /* Comments Section */
        .comments-section {
            padding: 15px;
            background: #f9f9f9;
            border-top: 1px solid #eee;
            margin-top: 15px;
        }
        
        .comments-section h4 {
            margin: 0 0 10px;
            color: var(--primary-color);
        }
        
        .comment {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .comment:last-child {
            border-bottom: none;
        }
        
        .comment strong {
            color: var(--primary-color);
        }
        
        .comment small {
            color: #999;
            font-size: 12px;
        }
        
        /* Rating Section */
        .project-rating {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .project-rating h4 {
            margin: 0 0 10px;
            color: var(--primary-color);
        }
        
        .project-rating p {
            font-weight: 600;
            margin: 0 0 10px;
        }
        
        .rating-stars {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .rating-stars input[type="radio"] {
            display: none;
        }
        
        .rating-stars label {
            color: #ddd;
            font-size: 24px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .rating-stars label:hover,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .modal h2 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: var(--primary-color);
        }
        
        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        input, textarea {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            transition: var(--transition);
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(138,0,0,0.1);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        button[type="submit"] {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        button[type="submit"]:hover {
            background: var(--secondary-color);
        }
        
        /* Messages */
        .success-message, .error-message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .success-message {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .error-message {
            background: rgba(244, 67, 54, 0.2);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-header {
                margin-left: 0;
                padding: 15px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .projects-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'user_sidebar.php'; ?>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <div>
                <h1>Portfolio</h1>
                <p>JT Kitchen Equipment Installation Services</p>
            </div>
            <div class="header-right">
                <span id="currentDateTime"></span>
                <div class="profile-menu">
                    <i class="fas fa-user-circle" onclick="toggleMenu()"></i>
                    <div class="dropdown" id="dropdownMenu">
                        <ul>
                            <li onclick="window.location.href='settings.php'">
                                <i class="fas fa-user-edit"></i>
                                <span>Edit Profile</span>
                            </li>
                            <li onclick="window.location.href='logout.php'">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Log Out</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1><?= $isAdmin ? 'Manage Portfolio' : 'Our Projects' ?></h1>
            <?php if ($isAdmin): ?>
                <button id="toggleArchivedView" class="status-tag view" onclick="toggleArchivedProjects()">
                    <i class="fas fa-archive"></i>
                    <span>Show Archived Projects</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Add Project Button (for admins) -->
<?php if ($isAdmin): ?>
    <button class="add-button" onclick="openAddProjectModal()">
        <i class="fas fa-plus"></i>
    </button>
<?php endif; ?>
        
        <!-- Projects List -->
        <div class="projects-list">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                    <div class="project-card" data-is-archived="<?= $project['is_archived'] ?>">
                        <img src="<?= htmlspecialchars($project['image_path']) ?>" alt="<?= htmlspecialchars($project['title']) ?>">
                        <div class="project-card-content">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <p><?= htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : '') ?></p>
                            
                            <!-- Comment Button -->
                            <button class="comment-button" onclick="toggleComments(<?= $project['project_id'] ?>)">
                                <i class="fas fa-comment"></i> Comments
                            </button>
                            
                            <!-- Comments Section -->
                            <div id="comments-section-<?= $project['project_id'] ?>" class="comments-section" style="display: none;">
                                <h4>Comments</h4>
                                <div id="comments-container-<?= $project['project_id'] ?>"></div>
                                <form id="commentForm-<?= $project['project_id'] ?>" data-project-id="<?= $project['project_id'] ?>">
                                    <textarea name="comment" placeholder="Add a comment..." required></textarea>
                                    <button type="submit">Post Comment</button>
                                </form>
                            </div>
                            
                            <!-- Ratings Section -->
                            <div class="project-rating">
                                <h4>Rating</h4>
                                <?php
                                try {
                                    $stmt = $db->prepare("SELECT AVG(rating) AS average_rating FROM ratings WHERE project_id = :project_id");
                                    $stmt->bindParam(':project_id', $project['project_id'], PDO::PARAM_INT);
                                    $stmt->execute();
                                    $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $averageRating = $ratingData['average_rating'] ? round($ratingData['average_rating'], 1) : 'Not rated yet';
                                } catch (PDOException $e) {
                                    error_log("Database query failed: " . $e->getMessage());
                                    $averageRating = 'Error loading rating';
                                }
                                ?>
                                <p id="average-rating-<?= $project['project_id'] ?>">
                                    <i class="fas fa-star" style="color: #ffc107;"></i> 
                                    <?= htmlspecialchars($averageRating) ?>
                                </p>
                                
                                <form id="ratingForm-<?= $project['project_id'] ?>" data-project-id="<?= $project['project_id'] ?>">
                                    <div class="rating-stars">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" id="star-<?= $project['project_id'] ?>-<?= $i ?>" name="rating" value="<?= $i ?>">
                                            <label for="star-<?= $project['project_id'] ?>-<?= $i ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="submit">Submit Rating</button>
                                </form>
                            </div>
                            
                            <!-- Edit/Archive Buttons (Only for Admins) -->
                            <?php if ($isAdmin): ?>
                                <div class="project-actions">
                                    <button class="edit-button" onclick="event.stopPropagation(); showEditModal(<?= $project['project_id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($project['is_archived']): ?>
                                        <button class="post-again-button" onclick="event.stopPropagation(); toggleArchive(<?= $project['project_id'] ?>)">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="archive-button" onclick="event.stopPropagation(); toggleArchive(<?= $project['project_id'] ?>)">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open" style="font-size: 50px; color: #ccc;"></i>
                    <h3>No Projects Found</h3>
                    <p>There are currently no projects to display.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Project Modal -->
    <?php if ($isAdmin): ?>
        <div id="addProjectModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddProjectModal()">&times;</span>
                <h2>Add New Project</h2>
                <form id="addProjectForm" method="POST" action="add_project.php" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Project Title" required>
                    <textarea name="description" placeholder="Project Description" required></textarea>
                    <input type="file" name="image" accept="image/*" required>
                    <button type="submit">Add Project</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        // Update current date and time
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
        }
        
        // Initialize and update every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);
        
        // Toggle dropdown menu
        function toggleMenu() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.profile-menu i') && !e.target.closest('.dropdown')) {
                const dropdown = document.getElementById('dropdownMenu');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
        
        // Toggle Comments Section
        async function toggleComments(projectId) {
            const commentsSection = document.getElementById(`comments-section-${projectId}`);
            const isVisible = commentsSection.style.display === 'block';
            
            commentsSection.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                const commentsContainer = document.getElementById(`comments-container-${projectId}`);
                if (commentsContainer.innerHTML.trim() === '') {
                    await fetchComments(projectId, commentsContainer);
                }
            }
        }
        
        // Fetch Comments with enhanced error handling
async function fetchComments(projectId, container) {
    try {
        // Show loading state
        container.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading comments...</p>';
        
        // Make the request
        const response = await fetch(`get_comments.php?project_id=${projectId}`);
        
        // Log response details for debugging
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Raw response:', responseText);

        if (!response.ok) {
            throw new Error(`Server returned ${response.status}: ${responseText}`);
        }

        // Try to parse JSON
        let comments;
        try {
            comments = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid response format from server');
        }

        // Handle the comments data
        if (comments.error) {
            // Server returned an explicit error message
            container.innerHTML = `<p class="error-message">${escapeHtml(comments.error)}</p>`;
            return;
        }

        if (!Array.isArray(comments)) {
            throw new Error('Expected array of comments but got: ' + typeof comments);
        }

        if (comments.length > 0) {
            let commentsHTML = '';
            comments.forEach(comment => {
                if (!comment.username || !comment.comment || !comment.created_at) {
                    console.warn('Invalid comment structure:', comment);
                    return;
                }
                
                commentsHTML += `
                    <div class="comment">
                        <strong>${escapeHtml(comment.username)}</strong>
                        <p>${escapeHtml(comment.comment)}</p>
                        <small>${new Date(comment.created_at).toLocaleString()}</small>
                    </div>
                `;
            });
            
            if (commentsHTML === '') {
                container.innerHTML = '<p>No valid comments to display</p>';
            } else {
                container.innerHTML = commentsHTML;
            }
        } else {
            container.innerHTML = `
                <p class="empty-state">
                    <i class="far fa-comment-dots"></i>
                    No comments yet. Be the first to comment!
                </p>
            `;
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        container.innerHTML = `
            <p class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Error loading comments: ${escapeHtml(error.message)}
            </p>
        `;
    }
}

// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

     // Handle Add Project Form Submission
document.getElementById('addProjectForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('add_project.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                window.location.reload(); // Refresh to show new project
            } else {
                alert(result.message || 'Failed to add project');
            }
        } else {
            alert('Server error occurred');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});
        
        // Handle Rating Submission
document.querySelectorAll('[id^=ratingForm-]').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const projectId = form.dataset.projectId;
        const ratingInput = form.querySelector('input[name="rating"]:checked');
        
        if (!ratingInput) {
            alert('Please select a rating by clicking on the stars');
            return;
        }
        
        const rating = ratingInput.value;
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        try {
            const response = await fetch('add_rating.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `project_id=${projectId}&rating=${rating}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById(`average-rating-${projectId}`).innerHTML = 
                    `<i class="fas fa-star" style="color: #ffc107;"></i> ${data.average_rating} (${data.total_ratings} ratings)`;
                
                // Reset star selection
                form.querySelectorAll('input[name="rating"]').forEach(input => {
                    input.checked = false;
                });
            } else {
                alert(data.message || 'Failed to submit rating');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Rating';
        }
    });
});
        
        // Toggle Archived Projects
        let showArchived = false;
        function toggleArchivedProjects() {
            showArchived = !showArchived;
            const button = document.getElementById('toggleArchivedView');
            button.innerHTML = showArchived 
                ? '<i class="fas fa-eye-slash"></i><span>Hide Archived</span>' 
                : '<i class="fas fa-archive"></i><span>Show Archived</span>';
                
            document.querySelectorAll('.project-card').forEach(card => {
                const isArchived = card.dataset.isArchived === '1';
                card.style.display = (showArchived && isArchived) || (!showArchived && !isArchived) ? 'block' : 'none';
            });
        }
        
        // Archive/Unarchive Project
        async function toggleArchive(projectId) {
            if (!confirm('Are you sure you want to toggle this project?')) return;
            
            try {
                const response = await fetch('archive_project.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `project_id=${projectId}`
                });
                
                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }
        
        // Modal Functions
        function openAddProjectModal() {
            document.getElementById('addProjectModal').style.display = 'flex';
        }
        
        function closeAddProjectModal() {
            document.getElementById('addProjectModal').style.display = 'none';
        }
        
        // Helper function to escape HTML
        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;')
                     .replace(/</g, '&lt;')
                     .replace(/>/g, '&gt;')
                     .replace(/"/g, '&quot;')
                     .replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>