<?php if ($isAdmin): ?>
    <div id="addProjectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddProjectModal()">&times;</span>
            <h2>Add New Project</h2>
            <form id="addProjectForm" method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Project Title" required>
                <textarea name="description" placeholder="Project Description" required></textarea>
                <input type="file" name="image" accept="image/*" required>
                <button type="submit">Add Project</button>
            </form>
        </div>
    </div>
<?php endif; ?>
