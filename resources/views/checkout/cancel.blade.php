<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - EMURIA Property Care</title>
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

        .cancel-container {
            max-width: 600px;
            text-align: center;
        }

        .cancel-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #ff6b6b, #ff4444);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 40px;
            animation: scaleIn 0.5s ease-out;
        }

        .cancel-icon svg {
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
            color: #fff;
        }

        .message {
            font-size: 1.3rem;
            color: #b0b0b0;
            margin-bottom: 50px;
            line-height: 1.8;
        }

        .info-box {
            background: rgba(255, 184, 0, 0.05);
            border: 2px solid rgba(255, 184, 0, 0.3);
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

        .info-box p {
            color: #d0d0d0;
            line-height: 1.8;
            margin-bottom: 15px;
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
    <div class="cancel-container">
        <div class="cancel-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </div>

        <h1>Payment Cancelled</h1>
        
        <p class="message">
            Your payment was cancelled and no charges were made to your account.
        </p>

        <div class="info-box">
            <h3>What Happened?</h3>
            <p>
                You cancelled the payment process before completing your subscription. 
                Your account has been created but your subscription is not active yet.
            </p>
            <p>
                You can return to the pricing page anytime to select a plan and complete your subscription.
            </p>
        </div>

        <div>
            <a href="/tiers" class="btn">View Plans</a>
            <a href="/home/index.html" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>
