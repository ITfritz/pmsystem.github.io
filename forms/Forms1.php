<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Incidental Charges Form</title>
  <link rel="stylesheet" href="Forms1styles.css">
</head>
<body>
  <div class="form-container">
    <div class="form-header">
      <h2>JT KITCHEN EQUIPMENT INSTALLATION SERVICE</h2>
      <p>(Service provider of Middleby Worldwide Phils.)</p>
      <h3>INCIDENTAL CHARGES</h3>
    </div>

    <div class="form-row">
      <label>Customer Name: <input type="text" id="customer name"></label>
      <label>Date: <input type="date" id="date"></label>
      <label>No.: <input type="text" id="formNo" value="000052"></label>
    </div>
    <div class="form-row">
      <label>Location: <input type="text" id="location"></label>
      <label>Ref. SR No.: <input type="text" id="refNo"></label>
    </div>

    <div class="form-section">
      <strong>A. Transportation Expense</strong><br>
      <label class="checkbox-label"><input type="checkbox" id="serviceVan"> Using Service Van</label>
      <label>Kms: <input type="number" id="kms"> x Php <input type="number" id="vanRate"></label>
      <br>
      <label class="checkbox-label"><input type="checkbox" id="commute"> Commute</label>

      <table id="transportTable">
        <tr><th>From</th><th>To</th><th>Amount</th></tr>
        <tr><td><input type="text"></td><td><input type="text"></td><td><input type="number" class="transportAmount"></td></tr>
        <tr><td><input type="text"></td><td><input type="text"></td><td><input type="number" class="transportAmount"></td></tr>
        <tr><td><input type="text"></td><td><input type="text"></td><td><input type="number" class="transportAmount"></td></tr>
        <tr><td><input type="text"></td><td><input type="text"></td><td><input type="number" class="transportAmount"></td></tr>
        <tr><td><input type="text"></td><td><input type="text"></td><td><input type="number" class="transportAmount"></td></tr>
        <tr><td><input type="text"></td><td><input type="text"></td><td><input type="number" class="transportAmount"></td></tr>
      </table>
    </div>

    <div class="form-section">
      <strong>B. Travel Time Allow</strong>
      <label>Hrs: <input type="number" id="travelHrs"></label>
      <label>Rate: Php <input type="number" id="travelRate"></label>
    </div>

    <div class="form-section">
      <strong>C. Meal Allowance</strong><br>
      <label class="checkbox-label"><input type="checkbox" class="meal" data-amount="100"> Breakfast (P100)</label>
      <label>Amount: <input type="number" class="mealAmount"></label><br>
      <label class="checkbox-label"><input type="checkbox" class="meal" data-amount="120"> Lunch (P120)</label>
      <label>Amount: <input type="number" class="mealAmount"></label><br>
      <label class="checkbox-label"><input type="checkbox" class="meal" data-amount="200"> Dinner (P200)</label>
      <label>Amount: <input type="number" class="mealAmount"></label><br>
      <label class="checkbox-label"><input type="checkbox" class="meal" data-amount="0"> Per Diem (Overnight)</label>
      <label>Amount: <input type="number" class="mealAmount"></label>
    </div>

    <div class="form-section">
      <strong>D. Hotel Accommodation</strong>
      <label><input type="number" id="hotelDays"> days</label>
      <label>Amount: Php <input type="number" id="hotelAmount"></label>
    </div>

    <div class="form-row">
      <label>Total Amount: Php <input type="number" id="totalAmount" readonly></label>
    </div>

    <div class="form-row">
      <label>Prepared by: <input type="text"></label>
      <label>Approved by: <input type="text"></label>
      <label>Customer: <input type="text"></label>
    </div>

    <div class="form-section">
      <label class="checkbox-label"><input type="checkbox"> For Billing</label>
      <label class="checkbox-label"><input type="checkbox"> For File</label>
      <br>
      <small>Received Payment By: _______________________<br>
      Copy Distribution: White - Original, Blue - Acctg, Yellow - Customer</small>
    </div>

    <button onclick="calculateTotal()">Calculate Total</button>
  </div>

  <script src="Forms1script.js"></script>
</body>
</html>
