<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - EMURIA Property Care</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .success-container {
            max-width: 600px;
            text-align: center;
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 40px;
            animation: scaleIn 0.5s ease-out;
        }

        .success-icon svg {
            width: 60px;
            height: 60px;
            fill: #fff;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, #FFB800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .message {
            font-size: 1.3rem;
            color: #b0b0b0;
            margin-bottom: 50px;
            line-height: 1.8;
        }

        .info-box {
            background: rgba(255, 184, 0, 0.1);
            border: 2px solid #FFB800;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            text-align: left;
        }

        .info-box h3 {
            color: #FFB800;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
        }

        .info-box li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            color: #d0d0d0;
        }

        .info-box li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .btn {
            display: inline-block;
            padding: 18px 50px;
            background: linear-gradient(135deg, #FFB800 0%, #ff6b00 100%);
            color: #000;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            transition: all 0.3s ease;
            margin: 10px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 184, 0, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 2px solid rgba(255, 184, 0, 0.5);
        }

        .test-mode-note {
            margin-top: 40px;
            padding: 20px;
            background: rgba(40, 167, 69, 0.1);
            border: 2px solid #28a745;
            border-radius: 10px;
            color: #28a745;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            
            .message {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
        </div>

        <h1>Welcome to EMURIA!</h1>
        
        <p class="message">
            Your account has been successfully created and your subscription is now active. 
            We're excited to begin your property regeneration journey!
        </p>

        <div class="info-box">
            <h3>What's Next?</h3>
            <ul>
                <li>Access your personalized dashboard</li>
                <li>Add your first property</li>
                <li>Schedule an initial inspection</li>
                <li>Explore your tier benefits</li>
                <li>Contact your dedicated care team</li>
            </ul>
        </div>

        <div>
            <a href="/dashboard" class="btn">Go to Dashboard</a>
        </div>

        <div class="test-mode-note">
            ✅ TEST MODE SUCCESSFUL: This was a test transaction. No real charges were made.
        </div>
    </div>
</body>
</html>
