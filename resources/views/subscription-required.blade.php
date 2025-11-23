<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Required - EMURIA PropertyCare</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .icon {
            font-size: 80px;
            color: #FFB800;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        h1 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
        }

        .btn-secondary {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: transparent;
            color: #2ecc71;
            text-decoration: none;
            border: 2px solid #2ecc71;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #2ecc71;
            color: white;
        }

        .features {
            text-align: left;
            margin: 40px 0;
            padding: 20px;
            background: #f7fafc;
            border-radius: 10px;
        }

        .features h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .features ul {
            list-style: none;
        }

        .features li {
            padding: 10px 0;
            color: #4a5568;
            display: flex;
            align-items: center;
        }

        .features li i {
            color: #2ecc71;
            margin-right: 10px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-crown"></i>
        </div>
        
        <h1>Subscription Required</h1>
        
        <p>
            To access your dashboard and manage your properties, you need an active subscription plan. 
            Choose a plan that fits your needs and start managing your properties today!
        </p>

        <div class="features">
            <h3>What You'll Get:</h3>
            <ul>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Property Management Dashboard</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Real-time Inspections & Reports</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Project Tracking & Analytics</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Automated Invoicing</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>24/7 Support</span>
                </li>
            </ul>
        </div>

        <a href="{{ route('register') }}" class="btn">
            <i class="fas fa-rocket"></i> Get Started FREE
        </a>

        <br>

        <a href="{{ route('logout') }}" 
           class="btn-secondary"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</body>
</html>
