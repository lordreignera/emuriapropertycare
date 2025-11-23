<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Care Plan - EMURIA Property Care</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
        }

        .header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff 0%, #FFB800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            font-size: 1.2rem;
            color: #b0b0b0;
        }

        .cadence-toggle {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 40px 0;
        }

        .cadence-btn {
            padding: 15px 40px;
            border: 2px solid #FFB800;
            background: transparent;
            color: #FFB800;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .cadence-btn.active {
            background: #FFB800;
            color: #000;
        }

        .cadence-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 184, 0, 0.3);
        }

        .discount-badge {
            display: inline-block;
            background: #28a745;
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .tiers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .tier-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 184, 0, 0.2);
            border-radius: 20px;
            padding: 40px 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .tier-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #FFB800, #ff6b00);
        }

        .tier-card:hover {
            transform: translateY(-10px);
            border-color: #FFB800;
            box-shadow: 0 20px 40px rgba(255, 184, 0, 0.2);
        }

        .tier-card.popular {
            border-color: #FFB800;
            background: rgba(255, 184, 0, 0.1);
            transform: scale(1.05);
        }

        .popular-badge {
            position: absolute;
            top: 20px;
            right: -30px;
            background: #FFB800;
            color: #000;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-weight: 700;
            font-size: 0.85rem;
        }

        .tier-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #FFB800;
        }

        .tier-price {
            font-size: 3rem;
            font-weight: 700;
            margin: 20px 0;
            color: #fff;
        }

        .tier-price small {
            font-size: 1.2rem;
            color: #b0b0b0;
        }

        .tier-features {
            list-style: none;
            margin: 30px 0;
            text-align: left;
        }

        .tier-features li {
            padding: 12px 0;
            padding-left: 30px;
            position: relative;
            color: #d0d0d0;
            font-size: 0.95rem;
        }

        .tier-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #FFB800;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .select-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #FFB800 0%, #ff6b00 100%);
            color: #000;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .select-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 184, 0, 0.4);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #FFB800;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .tiers-grid {
                grid-template-columns: 1fr;
            }
            
            .tier-card.popular {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/home/index.html" class="back-link">← Back to Home</a>
        
        <div class="header">
            <h1>Choose Your Care Plan</h1>
            <p>Select the perfect tier for your property regeneration journey</p>
        </div>

        <div class="cadence-toggle">
            <button class="cadence-btn active" onclick="toggleCadence('monthly')">
                Monthly Billing
            </button>
            <button class="cadence-btn" onclick="toggleCadence('annual')">
                Annual Billing
                <span class="discount-badge">Save 8%</span>
            </button>
        </div>

        <div class="tiers-grid">
            @foreach($tiers as $index => $tier)
            <div class="tier-card {{ $index === 2 ? 'popular' : '' }}">
                @if($index === 2)
                <div class="popular-badge">POPULAR</div>
                @endif
                
                <div class="tier-name">{{ $tier->name }}</div>
                
                <div class="tier-price monthly-price">
                    ${{ number_format($tier->monthly_price, 0) }}
                    <small>/month</small>
                </div>
                
                <div class="tier-price annual-price" style="display: none;">
                    ${{ number_format($tier->annual_price, 0) }}
                    <small>/year</small>
                </div>

                <ul class="tier-features">
                    @php
                        $features = json_decode($tier->features, true);
                        $displayFeatures = array_slice($features, 0, 6);
                    @endphp
                    @foreach($displayFeatures as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                    @if(count($features) > 6)
                    <li>+ {{ count($features) - 6 }} more features</li>
                    @endif
                </ul>

                <button class="select-btn" onclick="selectTier({{ $tier->id }})">
                    Select {{ $tier->name }}
                </button>
            </div>
            @endforeach
        </div>
    </div>

    <script>
        let currentCadence = 'monthly';

        function toggleCadence(cadence) {
            currentCadence = cadence;
            
            // Update button states
            document.querySelectorAll('.cadence-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('.cadence-btn').classList.add('active');

            // Toggle price displays
            if (cadence === 'monthly') {
                document.querySelectorAll('.monthly-price').forEach(el => el.style.display = 'block');
                document.querySelectorAll('.annual-price').forEach(el => el.style.display = 'none');
            } else {
                document.querySelectorAll('.monthly-price').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.annual-price').forEach(el => el.style.display = 'block');
            }
        }

        function selectTier(tierId) {
            window.location.href = `/tiers/${tierId}/register?cadence=${currentCadence}`;
        }
    </script>
</body>
</html>
