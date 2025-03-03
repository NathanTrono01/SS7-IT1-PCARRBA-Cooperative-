<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smooth Ripple Button</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .btn-container {
            position: relative;
        }

        .btn {
            position: relative;
            overflow: hidden;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: #007BFF;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            outline: none;
            transition: transform 0.2s ease-in-out;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .btn:active {
            transform: scale(0.95);
        }

        .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            transform: scale(0);
            opacity: 1;
            animation: rippleEffect 2s ease-out forwards;
        }

        @keyframes rippleEffect {
            50% {
                transform: scale(12);
                opacity: 0.8;
            }
            100% {
                transform: scale(14);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

<div class="btn-container">
        <button type="submit" class="btn" onclick="createRipple(event)">Click Me</button>
</div>

<script>
    function createRipple(event) {
        const button = event.currentTarget;
        const ripple = document.createElement("span");
        ripple.classList.add("ripple");

        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 2.5; // Bigger ripple
        ripple.style.width = ripple.style.height = `${size}px`;

        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        button.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 2000); // Matches animation duration
    }
</script>

</body>
</html>
