<div>

    <div class="container">
        <div class="dot ">

        </div>
    </div>

    <style>
        .container {
            --uib-size: 40px;
            --uib-color: rgb(0, 0, 0);
            --uib-speed: 2s;
            --uib-dot-size: calc(var(--uib-size) * 0.24);
            position: relative;
            padding: 3px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--uib-dot-size);
            width: var(--uib-size);
        }

        .dot,
        .container::before,
        .container::after {
            content: '';
            display: block;
            height: var(--uib-dot-size);
            width: var(--uib-dot-size);
            border-radius: 50%;
            background-color: var(--uib-color);
            transform: scale(0);
            transition: background-color 0.3s ease;
        }

        .container::before {
            animation: pulse var(--uib-speed) ease-in-out calc(var(--uib-speed) * -0.375) infinite;
        }

        .dot {
            animation: pulse var(--uib-speed) ease-in-out calc(var(--uib-speed) * -0.25) infinite both;
        }

        .container::after {
            animation: pulse var(--uib-speed) ease-in-out calc(var(--uib-speed) * -0.125) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(0);
            }

            50% {
                transform: scale(1);
            }
        }
    </style>
</div>