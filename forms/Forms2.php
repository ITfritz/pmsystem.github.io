<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Service Report</title>
    <link rel="stylesheet" href="Forms2styles.css">
</head>
<body>
    <div class="form-container">
        <div class="header">
            <div class="logo">
                <img src="no-bg logo.png" alt="no-bg logo.png"> <div class="company-info">
                    <p class="company-name">JT KITCHEN EQUIPMENT</p>
                    <p class="service-name">INSTALLATION SERVICE</p>
                    <p class="small-text">(Supplier Provider of Quality by Workmanship Only)</p>
                    <p class="address">Blk 3 Lot 3, Pajico, Kapatagan Village, Canlubang, Calamba City, Laguna, 4027</p>
                    <p class="contact">Contact No.: 0925-502-0010 / 0925-502-0010 / 0925-502-0010</p>
                </div>
            </div>
            <div class="report-id">
                <span class="no">No.:</span> <span class="id-number">000091</span>
            </div>
        </div>

        <h1>FIELD SERVICE REPORT</h1>
        <p class="form-instruction">FORM MUST BE FILLED OUT IN T1 - PLEASE TYPE OR PRINT CLEARLY IN ENGLISH</p>

        <div class="section-row header-row top-section">
            <div class="col-33">MANUFACTURER / BRAND</div>
            <div class="col-33">SERVICE GROUP</div>
            <div class="col-33">CALL CODE:</div>
        </div>
        <div class="section-row input-row top-section">
            <div class="col-33"><input type="text" name="manufacturer"></div>
            <div class="col-33"><input type="text" name="service_group"></div>
            <div class="col-33"><input type="text" name="call_code"></div>
        </div>

        <div class="section-title">SECTION 1 - Customer Information</div>
        <div class="form-grid customer-info-grid">
            <div class="grid-item label">Customer:</div>
            <div class="grid-item"><input type="text" name="customer_name"></div>
            <div class="grid-item label">Company:</div>
            <div class="grid-item"><input type="text" name="company_name"></div>
            <div class="grid-item label">Store-Code:</div>
            <div class="grid-item"><input type="text" name="store_code"></div>
            <div class="grid-item label">Phone Number:</div>
            <div class="grid-item"><input type="text" name="phone_number"></div>

            <div class="grid-item label">Store Location:</div>
            <div class="grid-item full-width"><input type="text" name="store_location"></div>
            <div class="grid-item label">Address:</div>
            <div class="grid-item full-width"><input type="text" name="address"></div>

            <div class="grid-item label">Service Requested By:</div>
            <div class="grid-item service-request-options">
                <label><input type="checkbox" name="request_type" value="PWC"> PWC</label>
                <label><input type="checkbox" name="request_type" value="CHARGE"> CHARGE</label>
                <label><input type="checkbox" name="request_type" value="WTY"> WTY</label>
            </div>
            <div class="grid-item label">Reported Complaint:</div>
            <div class="grid-item full-width"><input type="text" name="reported_complaint"></div>

            <div class="grid-item label">Model:</div>
            <div class="grid-item"><input type="text" name="model"></div>
            <div class="grid-item label">Serial Number:</div>
            <div class="grid-item full-width"><input type="text" name="serial_number"></div>

            <div class="grid-item label">Phase (1, 2, 3) / kW/Hr/Rating:</div>
            <div class="grid-item"><input type="text" name="phase_rating"></div>
            <div class="grid-item label">Voltage Rating:</div>
            <div class="grid-item"><input type="text" name="voltage_rating"></div>
            <div class="grid-item label voltage-ampere-label">Voltage L1:</div>
            <div class="grid-item voltage-ampere-input"><input type="text" name="voltage_l1"></div>
            <div class="grid-item voltage-ampere-label">L2:</div>
            <div class="grid-item voltage-ampere-input"><input type="text" name="voltage_l2"></div>
            <div class="grid-item voltage-ampere-label">L3:</div>
            <div class="grid-item voltage-ampere-input"><input type="text" name="voltage_l3"></div>
            <div class="grid-item voltage-ampere-label">Ampere L1:</div>
            <div class="grid-item voltage-ampere-input"><input type="text" name="ampere_l1"></div>
            <div class="grid-item voltage-ampere-label">L2:</div>
            <div class="grid-item voltage-ampere-input"><input type="text" name="ampere_l2"></div>
            <div class="grid-item voltage-ampere-label">L3:</div>
            <div class="grid-item voltage-ampere-input"><input type="text" name="ampere_l3"></div>

            <div class="grid-item label findings-label">Findings:</div>
            <div class="grid-item full-width findings-area">
                <div class="findings-row">
                    <label>Gas Pressure:</label><input type="text" name="gas_pressure">
                    <label>Water Pressure:</label><input type="text" name="water_pressure">
                </div>
                <div class="findings-row">
                    <label>Ambient Temp:</label><input type="text" name="ambient_temp">
                    <label>Ventilation:</label><input type="text" name="ventilation">
                </div>
            </div>

            <div class="grid-item label full-width">Probable Cause/Recommendations:</div>
            <div class="grid-item full-width no-bottom-border"><textarea rows="3" name="probable_cause"></textarea></div>

            <div class="grid-item label full-width">Action Taken:</div>
            <div class="grid-item full-width no-bottom-border"><textarea rows="3" name="action_taken"></textarea></div>
        </div>

        <div class="section-title">SECTION 2 - Labor & Travel</div>
        <div class="labor-travel-grid">
            <div class="grid-header date-header">Date Installed</div>
            <div class="grid-header date-header">Date Service Requested</div>
            <div class="grid-header date-header">Date/Work Completed</div>

            <div class="date-row">
                <div class="date-input-wrapper"><input type="text" placeholder="dd/mm/yyyy" name="date_installed"><span class="calendar-icon">📅</span></div>
                <div class="date-input-wrapper"><input type="text" placeholder="dd/mm/yyyy" name="date_service_requested"><span class="calendar-icon">📅</span></div>
                <div class="date-input-wrapper"><input type="text" placeholder="dd/mm/yyyy" name="date_work_completed"><span class="calendar-icon">📅</span></div>
            </div>

            <div class="grid-header service-details-header">Service Claim</div>
            <div class="grid-header service-details-header">Equal from</div>
            <div class="grid-header service-details-header">Travel To</div>
            <div class="grid-header service-details-header">Total Hrs.</div>
            <div class="grid-header service-details-header">Rate / Hr.</div>
            <div class="grid-header service-details-header">Total Amt.</div>

            <div class="grid-header labor-expense-header">1. Labor</div>
            <div class="grid-header labor-expense-header">Hours</div>
            <div class="grid-header labor-expense-header">Rate/Hr.</div>
            <div class="grid-header labor-expense-header last-col">1. Php.</div>

            <input type="text" class="service-claim-input" name="service_claim_1">
            <input type="text" class="equal-from-input" name="equal_from_1">
            <input type="text" class="travel-to-input" name="travel_to_1">
            <input type="text" class="total-hrs-input" name="total_hrs_1">
            <input type="text" class="rate-hr-input" name="rate_hr_1">
            <input type="text" class="total-amt-input" name="total_amt_1">
            <div class="numbered-item">2. Supervising</div>
            <input type="text" name="supervising_hours">
            <input type="text" name="supervising_rate">
            <input type="text" class="last-col" value="2. Php." name="supervising_php">

            <input type="text" class="service-claim-input" name="service_claim_2">
            <input type="text" class="equal-from-input" name="equal_from_2">
            <input type="text" class="travel-to-input" name="travel_to_2">
            <input type="text" class="total-hrs-input" name="total_hrs_2">
            <input type="text" class="rate-hr-input" name="rate_hr_2">
            <input type="text" class="total-amt-input" name="total_amt_2">
            <div class="numbered-item">3. Travel Time</div>
            <input type="text" name="travel_time_hours">
            <input type="text" name="travel_time_rate">
            <input type="text" class="last-col" value="3. Php." name="travel_time_php">

            <input type="text" class="service-claim-input" name="service_claim_3">
            <input type="text" class="equal-from-input" name="equal_from_3">
            <input type="text" class="travel-to-input" name="travel_to_3">
            <input type="text" class="total-hrs-input" name="total_hrs_3">
            <input type="text" class="rate-hr-input" name="rate_hr_3">
            <input type="text" class="total-amt-input" name="total_amt_3">
            <div class="numbered-item">4. Transportation</div>
            <input type="text" name="transportation_hours">
            <input type="text" name="transportation_rate">
            <input type="text" class="last-col" value="4. Php." name="transportation_php">

            <div class="numbered-item meals-lodging-item">5. Meals</div>
            <input type="text" class="last-col" value="5. Php." name="meals_php">
            <div class="numbered-item meals-lodging-item">6. Lodging</div>
            <input type="text" class="last-col" value="6. Php." name="lodging_php">
            <div class="empty-cell"></div> <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>


            <div class="numbered-item meals-lodging-item">7. Per Diem</div>
            <input type="text" class="last-col" value="7. Php." name="per_diem_php">
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
            <div class="empty-cell"></div>
        </div>

        <div class="signature-payment-section">
            <div class="customer-accreditation">
                <h3>CUSTOMER ACCREDITATION:</h3>
                <p>We accept the above service rendered as proper, operative, and correct. We also acknowledge and find the above appliances and agree to pay to us the total charges noted herein.</p>
                <div class="signature-line">
                    <input type="text" class="signature-input" name="customer_signature_name" placeholder=" ">
                    <span class="label-below">Signature over Printed Name</span>
                    <input type="text" class="date-input" name="customer_signature_date" placeholder=" ">
                    <span class="label-below">Date</span>
                </div>
                <div class="signature-line">
                    <input type="text" class="signature-input" name="customer_designation" placeholder=" ">
                    <span class="label-below">Designation</span>
                    <input type="text" class="time-input" name="customer_time_in_out" placeholder=" ">
                    <span class="label-below">TIME IN / OUT</span>
                </div>
            </div>
            <div class="payment-summary">
                <div class="summary-row">
                    <label>DR/REF. NO.</label>
                    <input type="text" name="dr_ref_no">
                    <label>TOTAL PARTS</label>
                    <input type="text" name="total_parts">
                </div>
                <div class="summary-row">
                    <label>FREIGHT COST</label>
                    <input type="text" name="freight_cost">
                    <div class="empty-summary-cell"></div> </div>
                <div class="summary-row grand-total-row">
                    <label>GRAND TOTAL</label>
                    <input type="text" name="grand_total">
                    <div class="empty-summary-cell"></div> </div>

                <div class="certified-section">
                    <p>Certified by:</p>
                    <input type="text" name="certified_by_name" class="underline-input">
                    <p class="label-below-input">MECHANICIAN NAME AND SIGNATURE</p>
                </div>
                <div class="checked-section">
                    <p>Checked by:</p>
                    <input type="text" name="checked_by_name" class="underline-input">
                    <p class="label-below-input">PBS</p>
                </div>
                <div class="encoded-section">
                    <p>Encoded by:</p>
                    <input type="text" name="encoded_by_name" class="underline-input">
                    <p class="label-below-input">PSA</p>
                </div>
                <div class="footer-note">
                    <p>Original Copy to Office, Duplicate (White) to MSP Accounting, Triplicate (Pink) for Customer</p>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>