<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for {{ $tier->name }} - EMURIA Property Care</title>
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
            max-width: 800px;
            margin: 0 auto;
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

        .registration-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 184, 0, 0.3);
            border-radius: 20px;
            padding: 50px;
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #FFB800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .selected-tier {
            background: rgba(255, 184, 0, 0.1);
            border: 2px solid #FFB800;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 40px;
            text-align: center;
        }

        .tier-name {
            font-size: 1.8rem;
            color: #FFB800;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .tier-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
        }

        .tier-price small {
            font-size: 1rem;
            color: #b0b0b0;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #FFB800;
            font-weight: 600;
            font-size: 1rem;
        }

        input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 184, 0, 0.3);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #FFB800;
            background: rgba(255, 255, 255, 0.08);
        }

        input::placeholder {
            color: #808080;
        }

        .error {
            color: #ff4444;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #FFB800 0%, #ff6b00 100%);
            color: #000;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 30px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 184, 0, 0.4);
        }

        .security-note {
            text-align: center;
            margin-top: 25px;
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .security-note svg {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 5px;
            fill: #28a745;
        }

        .test-mode-banner {
            background: #28a745;
            color: #fff;
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .registration-card {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/tiers" class="back-link">← Back to Plans</a>
        
        <div class="registration-card">
            <div class="test-mode-banner">
                🛡️ TEST MODE: No real charges will be made. Use test card: 4242 4242 4242 4242
            </div>

            <div class="header">
                <h1>Create Your Account</h1>
            </div>

            <div class="selected-tier">
                <div class="tier-name">{{ $tier->name }}</div>
                <div class="tier-price">
                    ${{ $cadence === 'monthly' ? number_format($tier->monthly_price, 0) : number_format($tier->annual_price, 0) }}
                    <small>/{{ $cadence === 'monthly' ? 'month' : 'year' }}</small>
                </div>
                @if($cadence === 'annual')
                <div style="margin-top: 10px; color: #28a745; font-weight: 600;">
                    💰 Save 8% with annual billing
                </div>
                @endif
            </div>

            @if($errors->any())
            <div style="background: rgba(255, 68, 68, 0.1); border: 2px solid #ff4444; border-radius: 10px; padding: 20px; margin-bottom: 30px;">
                <ul style="list-style: none;">
                    @foreach($errors->all() as $error)
                    <li class="error">• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('checkout.process') }}">
                @csrf
                <input type="hidden" name="tier_id" value="{{ $tier->id }}">
                <input type="hidden" name="cadence" value="{{ $cadence }}">

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" 
                           placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" 
                           placeholder="john@example.com" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" 
                           placeholder="+256 708 356 505" required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Minimum 8 characters" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                           placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="submit-btn">
                    Continue to Payment
                </button>

                <div class="security-note">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    Secure payment powered by Stripe. Your data is encrypted and protected.
                </div>
            </form>
        </div>
    </div>
</body>
</html>
