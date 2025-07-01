// script.js

document.addEventListener('DOMContentLoaded', function() {
    // This function will run once the DOM is fully loaded.

    // Example of a simple interactive element:
    // If you had a button to clear the form, you could add an event listener.
     const clearButton = document.getElementById('clearForm');
     if (clearButton) {
        clearButton.addEventListener('click', function() {
            document.querySelector('.form-container').reset(); // If it was a <form> element
            alert('Form cleared!');
        });
    }

    // Example for dynamically updating a "total" field (if you had one)
    // This is just a conceptual example. You'd need to adapt it to your specific fields.
    
    const laborHoursInput = document.querySelector('.labor-travel-grid input[name="labor_hours"]');
    const laborRateInput = document.querySelector('.labor-travel-grid input[name="labor_rate"]');
    const laborTotalOutput = document.querySelector('.labor-travel-grid input[name="labor_total"]');

    function calculateLaborTotal() {
        const hours = parseFloat(laborHoursInput.value) || 0;
        const rate = parseFloat(laborRateInput.value) || 0;
        laborTotalOutput.value = (hours * rate).toFixed(2); // Format to 2 decimal places
    }

    if (laborHoursInput && laborRateInput && laborTotalOutput) {
        laborHoursInput.addEventListener('input', calculateLaborTotal);
        laborRateInput.addEventListener('input', calculateLaborTotal);
    }
    

    // General note: Since all input fields are already typeable by default due to HTML,
    // and checkboxes are clickable, specific JavaScript for this basic functionality
    // is not required. You would add JS here for form validation, data submission,
    // dynamic content, or complex calculations.
});