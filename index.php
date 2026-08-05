<!--
    Index.php - Main landing page for the WalangBrownout portal system.
    This page serves as the entry point for users, providing an overview of the system's features
    and a navigation menu to access different sections of the portal.
-->

<?php
    require_once 'session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WalangBrownout</title>
    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <link rel="stylesheet" href="index_style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<body class="app-shell text-slate-800">

    <div class="bg-aurora" aria-hidden="true">
        <div class="aurora-orb one"></div>
        <div class="aurora-orb two"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10">

        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">

            <div class="flex items-center space-x-3">

                <img src="https://img.sanishtech.com/u/6559c6ed2b30023d94b79a0932f09814.png"
                    alt="Walang Brown Out Logo"
                    width="45"
                    height="45">

                <div class="hidden sm:block leading-tight">

                    <span class="font-bold text-gray-700 text-[11px] uppercase tracking-[0.18em]">

                        Republic of the Philippines

                    </span>

                    <div class="text-lg font-extrabold text-blue-700">

                        WALANG BROWN OUT

                    </div>

                </div>

            </div>

            <div class="flex items-center gap-3">

                <button id="themeToggle"
                        type="button"
                        class="theme-toggle hidden md:inline-flex items-center gap-2">

                    🌙

                </button>

                <span class="hidden md:flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-600 shadow-sm">

                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>

                    Live System

                </span>

                <a href="login.php" class="btn">

                    Login to Portal

                </a>

            </div>

        </div>

    </header>

    <!-- Main Content -->
    <main>
        <div class="flex-1 space-y-6">
            
            <!-- SIDEBAR -->

            <aside class="w-24 bg-white border-r border-slate-200 fade-in-up shadow-sm">
                <div class="flex flex-col items-center py-8">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl shadow-sm">
                        ⚡
                    </div>

                    <div class="mt-10 space-y-5">
                        <div class="sidebar-icon w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center shadow-sm">
                            🏠
                        </div>

                        <div class="sidebar-icon w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center shadow-sm">
                            📦
                        </div>

                        <div class="sidebar-icon w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center shadow-sm">
                            👤
                        </div>

                        <div class="sidebar-icon w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center shadow-sm">
                            📊
                        </div>
                    </div>

                </div>
            </aside>

            <!-- HERO -->

            <section class="gradient-card rounded-[20px] p-6 md:p-8 border border-slate-200 fade-in-up hover-lift shadow-[0_14px_35px_rgba(15,23,42,0.05)]">
                <div class="grid lg:grid-cols-[1.1fr_0.9fr] gap-8">
                    
                    <!-- LEFT -->

                    <div>
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.2em] text-blue-700">
                            Enterprise Portal
                        </span>

                        <h1 class="text-4xl font-extrabold mt-5 leading-tight text-slate-900">
                            Walang Brown Out
                            <br>
                            Portal System
                        </h1>
                        
                        <p class="text-slate-600 mt-5 max-w-2xl">
                            A secure, role-based warehouse and inventory management
                            platform designed for administrators, warehouse staff,
                            suppliers, and employees.
                        </p>

                        <div class="flex flex-wrap gap-3 mt-6">
                            <a href="login.php" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold transition hover:scale-105">
                                Sign In
                            </a>

                            <a href="#features" class="border border-slate-300 px-6 py-3 rounded-xl font-semibold hover:bg-slate-100">
                                Explore Features
                            </a>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="accent-pill">Secure Access</span>
                            <span class="accent-pill">Warehouse Control</span>
                            <span class="accent-pill">Inventory Tracking</span>
                            <span class="accent-pill">Real-Time Dashboard</span>
                        </div>

                        <!-- ABOUT -->

                        <div class="mt-10">

                            <h2 class="text-2xl font-bold text-slate-900">
                                About Us
                            </h2>

                            <p class="mt-3 text-slate-600 leading-relaxed">
                                Walang Brown Out provides a centralized inventory,
                                warehouse, supplier, and user management system built
                                to improve operational efficiency. The portal enables
                                businesses to securely manage stock movement,
                                warehouse operations, employee workflows, and
                                administrative tasks through a modern and intuitive
                                interface.
                            </p>

                        </div>
                    </div>

                    <!-- RIGHT SIDE -->

                    <div class="space-y-4">

                        <!-- Warehouse -->

                        <div class="glass rounded-3xl p-5 fade-in-up-delay">
                            <div class="flex justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Warehouse Console
                                    </h3>

                                    <p class="text-sm text-slate-500">
                                        Monitoring all warehouse zones
                                    </p>
                                </div>

                                <span class="text-emerald-600 font-bold">
                                    Active
                                </span>
                            </div>

                            <div class="mt-5 h-2 rounded-full bg-slate-100">
                                <div class="h-2 w-4/5 rounded-full bg-blue-600"></div>
                            </div>
                        </div>

                        <!-- Admin -->

                        <div class="glass rounded-3xl p-5 fade-in-up-delay">
                            <div class="flex justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Admin Workflow
                                    </h3>

                                    <p class="text-sm text-slate-500">
                                        Fast approvals and routing
                                    </p>
                                </div>

                                <span class="text-blue-600 font-bold">
                                    Ready
                                </span>

                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <div class="flex-1 rounded-full bg-slate-100 p-1">
                                    <div class="h-2 w-3/4 rounded-full bg-slate-900"></div>
                                </div>

                                <span class="font-semibold">
                                    75%
                                </span>
                            </div>
                        </div>

                        <!-- Calendar -->

                        <div class="glass rounded-3xl p-5 fade-in-up-delay">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-slate-900">
                                        Calendar
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        Interactive Schedule
                                    </p>
                                </div>

                                <div class="flex gap-2">
                                    <button id="prevMonthBtn"
                                        class="rounded-full border border-slate-200 px-3 py-1">
                                        ←
                                    </button>

                                    <button id="nextMonthBtn"
                                        class="rounded-full border border-slate-200 px-3 py-1">
                                        →
                                    </button>
                                </div>
                            </div>

                            <div id="calendarHeader" class="mt-4 text-center font-semibold">
                            </div>

                            <div class="grid grid-cols-7 gap-1 mt-3 text-center text-xs font-semibold text-slate-500">
                                <span>Mo</span>
                                <span>Tu</span>
                                <span>We</span>
                                <span>Th</span>
                                <span>Fr</span>
                                <span>Sa</span>
                                <span>Su</span>
                            </div>

                            <div id="calendarGrid" class="grid grid-cols-7 gap-1 mt-2"></div>

                            <div id="selectedDateLabel" class="mt-3 text-center text-xs text-slate-500"></div>
                        </div>

                    </div>

                </div>
            </section>

            <section id="features" class="grid md:grid-cols-3 gap-5">

            </section>

        </div>
        
    </main>

    <!-- Footer -->
    <footer class="relative z-10 mt-10 border-t border-slate-200 bg-white/80 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-6 py-6 text-center text-slate-600 text-sm">
            <strong>&copy; 2026 WalangBrownOut.</strong>
            All rights reserved.
        </div>
    </footer>

    <!-- Interactive Calendar -->

    <script>
        const calendarHeader = document.getElementById('calendarHeader');
        const calendarGrid = document.getElementById('calendarGrid');
        const selectedDateLabel = document.getElementById('selectedDateLabel');
        const prevMonthBtn = document.getElementById('prevMonthBtn');
        const nextMonthBtn = document.getElementById('nextMonthBtn');

        if(calendarHeader){

            const state = {
                currentDate: new Date(),
                selectedDate: new Date()
            };

            function formatMonthTitle(date){
                return new Intl.DateTimeFormat(
                    'en',
                    {
                        month:'long',
                        year:'numeric'
                    }
                ).format(date);
            }

            function renderCalendar(){

                const year = state.currentDate.getFullYear();
                const month = state.currentDate.getMonth();

                const firstDay = new Date(year,month,1);

                const firstDayIndex = (firstDay.getDay()+6)%7;

                const daysInMonth = new Date(year,month+1,0).getDate();

                const prevMonthDays = new Date(year,month,0).getDate();

                const today = new Date();

                calendarHeader.textContent = formatMonthTitle(state.currentDate);

                calendarGrid.innerHTML = "";

                const cells = [];

                for(let i=0;i<firstDayIndex;i++){

                    cells.push({
                        day:prevMonthDays-firstDayIndex+i+1,
                        muted:true
                    });

                }

                for(let day=1;day<=daysInMonth;day++){

                    const isToday =
                        day===today.getDate() &&
                        month===today.getMonth() &&
                        year===today.getFullYear();

                    const isSelected =
                        day===state.selectedDate.getDate() &&
                        month===state.selectedDate.getMonth() &&
                        year===state.selectedDate.getFullYear();

                    cells.push({
                        day,
                        muted:false,
                        isToday,
                        isSelected
                    });

                }

                while(cells.length<42){

                    cells.push({
                        day:cells.length-daysInMonth-firstDayIndex+1,
                        muted:true
                    });

                }

                cells.forEach(cell=>{

                    const button=document.createElement("button");

                    button.type="button";

                    button.className=
                        "calendar-cell"+
                        (cell.muted?" muted":"")+
                        (cell.isToday?" today":"")+
                        (cell.isSelected?" selected":"");

                    button.textContent=cell.day;

                    if(!cell.muted){

                        button.onclick=()=>{

                            state.selectedDate=new Date(
                                year,
                                month,
                                cell.day
                            );

                            renderCalendar();

                        };

                    }

                    calendarGrid.appendChild(button);

                });

                selectedDateLabel.textContent =
                    "Selected: "+
                    state.selectedDate.toLocaleDateString(
                        "en-US",
                        {
                            month:"short",
                            day:"numeric",
                            year:"numeric"
                        }
                    );

            }

            prevMonthBtn.onclick=()=>{

                state.currentDate=
                    new Date(
                        state.currentDate.getFullYear(),
                        state.currentDate.getMonth()-1,
                        1
                    );

                renderCalendar();

            };

            nextMonthBtn.onclick=()=>{

                state.currentDate=
                    new Date(
                        state.currentDate.getFullYear(),
                        state.currentDate.getMonth()+1,
                        1
                    );

                renderCalendar();

            };

            renderCalendar();

        }
    </script>

    <!-- Theme Toggle -->

    <script>
        (function(){

            const savedTheme =
                localStorage.getItem("wbo-theme");

            const systemTheme =
                window.matchMedia("(prefers-color-scheme: dark)").matches
                    ? "dark"
                    : "light";

            const initialTheme =
                savedTheme || systemTheme;

            document.documentElement.setAttribute(
                "data-theme",
                initialTheme
            );

            window.addEventListener("DOMContentLoaded",()=>{

                const button =
                    document.getElementById("themeToggle");

                if(!button) return;

                button.textContent =
                    initialTheme==="dark"
                        ? "☀️"
                        : "🌙";

                button.onclick=()=>{

                    const next =
                        document.documentElement.getAttribute("data-theme")==="dark"
                            ? "light"
                            : "dark";

                    document.documentElement.setAttribute(
                        "data-theme",
                        next
                    );

                    localStorage.setItem(
                        "wbo-theme",
                        next
                    );

                    button.textContent =
                        next==="dark"
                            ? "☀️"
                            : "🌙";

                };

            });

        })();
    </script>
</body>
</html>

<?php

?>