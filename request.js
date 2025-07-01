document.addEventListener('DOMContentLoaded', function () {
    // Function to toggle the status of a request
    function toggleStatus(button) {
        const STATUSES = ['pending', 'to-review', 'in-progress', 'completed'];
        const DISPLAY_STATUSES = ['Pending', 'To Review', 'In Progress', 'Completed'];
    
        // Get current status from button text
        const currentStatus = button.textContent.trim().toLowerCase().replace(' ', '-');
        
        // Debugging: Log the current status
        console.log('Current Status:', currentStatus);

        const currentIdx = STATUSES.indexOf(currentStatus);

        if (currentIdx === -1) {
            alert('Invalid status detected!');
            return;
        }
    
        // Calculate next status
        const newStatus = STATUSES[(currentIdx + 1) % STATUSES.length];
        const displayStatus = DISPLAY_STATUSES[(currentIdx + 1) % DISPLAY_STATUSES.length];
    
        // Add loading state
        button.classList.add('updating');
        button.disabled = true;
    
        fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: button.dataset.requestId,
                status: newStatus
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Network Error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update button text and class
                button.textContent = displayStatus;
                button.className = 'status-tag ' + newStatus;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Update failed. Check console.');
        })
        .finally(() => {
            button.classList.remove('updating');
            button.disabled = false;
        });
    }

    // Function to toggle the height of the request section
    document.getElementById('toggle-btn').addEventListener('click', function() {
        const myList = document.querySelector('.my-requests');
        const icon = this.querySelector('i');
        
        myList.classList.toggle('minimized');
        
        if(myList.classList.contains('minimized')) {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        } else {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    });

    // Add event listeners to the status buttons
    document.querySelectorAll('.request ul li button').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation(); // Prevent the click event from bubbling up to the document
            toggleStatus(button);
        });
    });

    // Add event listener to the scroll indicator
    document.querySelector('.scroll-indicator').addEventListener('click', toggleRequestSection);
});

document.querySelectorAll('.request-item').forEach(item => {
    item.style.opacity = 0;
    setTimeout(() => item.style.opacity = 1, 50);
});
