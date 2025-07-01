function calculateTotal() {
  let total = 0;

  // Van transport
  const kms = parseFloat(document.getElementById('kms').value) || 0;
  const vanRate = parseFloat(document.getElementById('vanRate').value) || 0;
  total += kms * vanRate;

  // Commute amounts
  document.querySelectorAll('.transportAmount').forEach(input => {
    total += parseFloat(input.value) || 0;
  });

  // Travel allowance
  const hrs = parseFloat(document.getElementById('travelHrs').value) || 0;
  const rate = parseFloat(document.getElementById('travelRate').value) || 0;
  total += hrs * rate;

  // Meal allowance
  document.querySelectorAll('.mealAmount').forEach(input => {
    total += parseFloat(input.value) || 0;
  });

  // Hotel
  const hotel = parseFloat(document.getElementById('hotelAmount').value) || 0;
  total += hotel;

  document.getElementById('totalAmount').value = total.toFixed(2);
}
