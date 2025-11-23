ETOGO SUBSCRIPTION COSTING TEMPLATE: 
ETOGO PRICING ENGINE IMPORTANT 
ASSUMPTIONS 
These assumptions ensure that Etogo delivers value-aligned, fair, profitable, and transparent 
pricing while staying true to the real maintenance cycles of homes. 
1. Assumptions About Labour & Service Delivery 
1.1 Standard preventive maintenance visit = 1 hour of labour 
• Includes inspection, minor repairs, diagnostics, reporting, and travel. 
1.2 Technicians are cross-trained 
• Most work can be done by one skilled tech; complex cases may require two. 
1.3 Visit frequency reflects actual home needs, NOT a monthly cycle 
This is a core assumption: 
Homes do not deteriorate on a monthly cycle. 
Therefore: 
• Monthly visits are unnecessary 
• Preventive visits must be scheduled according to risk, wear, complexity, and home type, 
not the calendar 
• Most homes require only 2–7 visits per year, not 12 
This is foundational for cost fairness and customer value. 
2. Assumptions About Home Structure & Finish Complexity 
2.1 Structural Score (0–3) predicts labour complexity 
• More rooflines, height, cavities, crawlspaces → more time & skill. 
2.2 Finish Score (0–3) predicts labour sensitivity 
• Higher finishes require more precision, slower repair pace, and higher material cost. 
2.3 Structural + Finish scores represent ~90% of labour cost variation 
• This simplification keeps pricing predictable. 
3. Assumptions Built Into the Complexity Multiplier 
3.1 Each complexity point adds ~10% cost 
• Based on real-world labour/time burden in property maintenance. 
3.2 Multiplier range (1.00–1.60) covers all home types 
Basic → Premium → Estate. 
3.3 Complexity scoring keeps visits fair & proportional 
• A 1,000 sq ft basic home and a 5,000 sq ft estate cannot be the same price. 
4. Assumptions About Access Difficulty 
4.1 Access Score reflects true labour time + safety risk 
• Easy = 0 
• Moderate = 1 
• Difficult = 3 
4.2 Annual Access Adjustment = AccessScore × $50 
• Moderate access adds ~2–3 extra hours per year. 
• Difficult access adds ~8–12 extra hours per year. 
4.3 Most homes fall into Easy or Moderate 
This smooths labour demand across the portfolio. 
5. Assumptions About Home Value Premium 
5.1 High-value homes cost more to maintain 
Because finishes, materials, and systems are more fragile, costly, and complex. 
5.2 Industry premium averages run 8–25% 
Etogo uses a conservative, fair range of 8.5–22.5%. 
5.3 Premium encourages fairness—not profit padding 
Basic homes don’t subsidize luxury homes; each pays proportionally to their finish level. 
6. Assumptions About Visit Frequency 
6.1 Home type determines baseline visit needs 
Example: 
• Starter homes → 2–3 visits 
• Standard suburban → 3–4 visits 
• High-end → 5–7 visits 
• Estate homes → 6–9 visits 
6.2 Special conditions may increase visits 
Crawlspace, roof complexity, age (15+), high occupancy. 
6.3 MOST IMPORTANT: 
Homes do not need monthly visits. 
Home systems (plumbing, structure, roofing, seals, drainage, HVAC) generally deteriorate on: 
• seasonal cycles 
• usage cycles 
• age & wear cycles 
Not monthly ones. 
Therefore: 
• Monthly visit models are NOT value-aligned 
• Preventive cycles should match actual deterioration patterns 
• Subscription cost is NOT calculated as “12 × monthly visit price” 
This keeps pricing ethical, scalable, and customer-friendly. 
7. Assumptions About Subscription Economics 
7.1 Subscriptions replace emergency spending 
• Preventive visits reduce catastrophic failures. 
7.2 Subscription must deliver visible savings 
• Year 1 Savings = One-Time Project Cost – Subscription Price 
• Customers stay when value is measurable and real. 
7.3 Cross-subsidization is balanced and intentional 
• Basic homes cost less labour than subscription revenue 
• High-end homes cost more but pay proportionally more 
• This stabilizes labour load and profitability 
8. Assumptions on Transparency & Trust 
8.1 Homeowners must understand pricing in one sentence 
“Pricing is based on your home’s complexity, finish level, access difficulty, and how many visits 
your home actually needs — not a flat monthly visit model.” 
8.2 Scoring must be explainable & auditable 
• Structural score: visible roof/structure complexity 
• Finish score: visible material/finish levels 
• Access score: visible access difficulty 
8.3 Transparency reduces resistance 
Visible scoring builds trust and improves conversion. 
9. Assumptions About Data & Future Optimization 
9.1 Real usage data will refine scoring bands 
After observing 20–30 homes, Etogo will adjust: 
• Visit frequencies 
• Premium bands 
• Complexity score thresholds 
9.2 Model is designed to upgrade into AI pricing 
Each input can feed into an automated future pricing engine. 
9.3 Simplicity is strategic 
Too many variables confuse clients and slow development. 
FINAL SUMMARY BLOCK  
• Homes do NOT deteriorate on a monthly cycle. 
• Therefore Etogo does NOT base subscription prices on monthly visits. 
• Visit counts depend on actual preventive needs (2–9 per year depending on home). 
• Base visit cost = $150 → represents 1 hour of skilled labour. 
• Structural + Finish Scores capture 90% of complexity cost. 
• Complexity Multiplier = 1.00–1.60, increasing 10% per complexity point. 
• Access Score = 0 / 1 / 3 → +$0 / +$50 / +$150 per year. 
• Home Value Premium = 8.5–22.5% based on finish level. 
• Annual Subscription = Visits × Base × Multiplier + Access Adjustment. 
• Final Price = RAW × (1 + Premium). 
• Subscription must show Year 1 savings vs one-time project quote. 
• Transparent scoring builds trust and protects Etogo’s bottom line. 
OVERALL CALCULATION FLOW  
For every property, pricing is calculated in this order: 
1. Collect inputs (home type, structural complexity, finish level, access type, visits/year, 
etc.) 
2. Score complexity (structural + finish) → Complexity Multiplier 
3. Score access (easy / moderate / difficult) → Access Adjustment 
4. Set Home Value Premium % (via finish tier slider) 
5. Calculate RAW Subscription 
6. Apply Premium → Final Annual Subscription Price 
Optionally: 
7. Compare Final Annual Subscription Price vs One-Time Project Quote → Client 
Savings. 
2. Template Structure  
SECTION A – INPUTS (filled by inspector or system) 
Field 
Base Cost per Visit 
Home Type 
Structural Complexity 
Score 
Finish Level Score 
Access Type 
Access Score 
Visits per Year 
Type 
Text 
Allowed Values / Notes 
Number Default: 150 
Starter / Standard / Upper-Mid / High-End / Estate / Rental / 
Vacation 
Number 0–3 (0 = simple, 3 = very complex) 
Number 0–3 (0 = basic, 3 = luxury) 
Text 
Easy / Moderate / Difficult 
Number 0 / 1 / 3 (derived from Access Type) 
Number From visits table (e.g., 3, 4, 5, 7…) 
Home Value Premium % Number From slider (e.g., 0.085, 0.12, 0.225) 
One-Time Project Cost 
Number Total of chosen repairs (optional, for savings calc) 
You can automate many of these (e.g., Access Score, Visits per Year, Premium %) with 
dropdowns + formulas, but they’re all you need. 
SECTION B – DERIVED VALUES (Formulas) 
Use these core formulas in your template: 
1. Complexity Multiplier (B6) 
ComplexityMultiplier = 1 + (StructuralScore + FinishScore) / 10 
• StructuralScore = 0–3 
• FinishScore = 0–3 
• Range ≈ 1.00 to 1.60 
Examples: 
• Simple home: (0 + 0) → 1.00 
• Mid-tier: (1 + 1) → 1.20 
• High-end: (2 + 2) → 1.40 
• Estate: (3 + 3) → 1.60 
2. Access Adjustment (B7) 
First map Access Type → Access Score: 
• Easy → 0 
• Moderate → 1 
• Difficult → 3 
Then: 
AccessAdjustmentAnnual = AccessScore * 50 
• Easy (0) → $0 
• Moderate (1) → $50 
• Difficult (3) → $150 
3. RAW Subscription (before Home Value Premium) 
RawSubscription = VisitsPerYear * BaseCostPerVisit * ComplexityMultiplier 
+ AccessAdjustmentAnnual 
4. Final Annual Subscription Price 
FinalPrice = RawSubscription * (1 + HomeValuePremium) 
Where HomeValuePremium is a decimal (e.g., 10% → 0.10). 
5. Optional – Client Savings vs One-Time Project 
Year1Savings = OneTimeProjectCost - FinalPrice 
Use this to visibly show: 
“If you pay for these fixes individually, it’s $X. 
With Etogo Stewardship, it’s $Y/year and you save $Z.” 
SECTION C – LOOKUP TABLES (Simple, for dropdowns) 
1. Finish Level → Slider Band (Premium Range) 
Finish Tier Description Suggested Premium Range 
Basic 
Mid-tier 
High-end 
Builder grade 8.5–9.7% (0.085–0.097) 
Upgraded 
Premium 
Luxury/Estate Top tier 
10.5–12.5% (0.105–0.125) 
12.5–15.5% (0.125–0.155) 
19.5–22.5% (0.195–0.225) 
(You already have the checklist to decide which tier a home is.) 
2. Home Type → Recommended Visits Per Year 
Home Type 
Starter / Basic 
Standard Suburban 
Upper-Mid 
Visits / Year (Base) 
2–3 
3–4 
4–5 
Older Home (15+ yrs) 5–6 
High-End 
5–7 
Luxury / Estate 
Vacation Home 
6–9 
2–4 
High-Turnover Rental 4–7 
Then apply special conditions: 
• Crawlspace → +1–2 
• Complex rooflines → +1 
• High occupancy / large family → +1 
3. Minimal Example  
Here’s the whole thing in one compact block: 
INPUTS: 
BaseCostPerVisit          = 150 
StructuralScore           = 2 
FinishScore               = 2 
AccessScore               = 1 
VisitsPerYear             = 5 
HomeValuePremium          = 0.15   (15%) 
OneTimeProjectCost        = 3000   (optional) 
DERIVED: 
ComplexityMultiplier      = 1 + (StructuralScore + FinishScore) / 10 
= 1 + (2 + 2) / 10 
= 1.40 
AccessAdjustmentAnnual    = AccessScore * 50 
= 1 * 50 
= 50 
RawSubscription           = VisitsPerYear * BaseCostPerVisit * 
ComplexityMultiplier 
+ AccessAdjustmentAnnual 
= 5 * 150 * 1.40 + 50 
= 1100 
FinalPrice                = RawSubscription * (1 + HomeValuePremium) 
= 1100 * 1.15 
= 1265 
Year1Savings              = OneTimeProjectCost - FinalPrice 
= 3000 - 1265 
= 1735 
That’s your value-aligned price (protects bottom line) + clear savings story.