<template>
    <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="logo-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#818cf8" />
                <stop offset="100%" stop-color="#4f46e5" />
            </linearGradient>
            <filter id="logo-shadow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="0" dy="12" stdDeviation="12" flood-color="#000000" flood-opacity="0.15" />
            </filter>
        </defs>
        <!-- Background -->
        <rect width="512" height="512" rx="140" fill="url(#logo-grad)" />
        <!-- Paper Airplane -->
        <g transform="translate(256, 256) scale(15) rotate(-45) translate(-12, -12)" filter="url(#logo-shadow)">
            <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.53 60.53 0 0 0 18.425-8.906.75.75 0 0 0 0-1.178 60.53 60.53 0 0 0-18.425-8.907Z" fill="#ffffff" />
        </g>
    </svg>
</template>
