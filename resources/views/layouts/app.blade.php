<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ config('app.name', 'AMBAJA 2026') }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body class="bg-[#F6F8FB] text-slate-800">

<div class="flex min-h-screen">


    {{-- ============================================================
        SIDEBAR
    ============================================================ --}}

    <aside
        class="
            sticky top-0
            flex h-screen w-64 flex-shrink-0 flex-col
            border-r border-white/10
            bg-[#0F8B8D]
            text-white
            shadow-xl
        "
    >


        {{-- LOGO / BRAND --}}
        <div
            class="
                flex h-20 flex-shrink-0
                items-center
                border-b border-white/15
                px-5
            "
        >

            <a href="{{ route('profil-perusahaan.index') }}" class="flex items-center gap-3 group transition hover:opacity-90" title="Kelola Profil Perusahaan (CV Andika Mustika)">

                <div
                    class="
                        flex h-11 w-10
                        items-center justify-center
                        rounded-xl bg-white p-1.5
                        shadow-sm ring-1 ring-white/20
                    "
                >

                    <img
                        src="{{ asset('images/LogoAMBAJA.png') }}"
                        alt="Logo CV Andika Mustika"
                        class="max-h-full max-w-full object-contain"
                    >

                </div>


                <div>

                    <div
                        class="
                            text-[15px]
                            font-bold
                            leading-tight
                            text-white
                            group-hover:text-white
                            transition
                        "
                    >
                        AMBAJA
                    </div>

                    <div class="mt-0.5 text-[10px] text-white/90 group-hover:text-white transition">
                        CV Andika Mustika
                    </div>

                </div>

            </a>

        </div>



        {{-- ========================================================
            MENU
        ======================================================== --}}

        @include('layouts.partials.sidebar-menu')



        {{-- ========================================================
            USER AREA
        ======================================================== --}}

        <div
            class="
                flex-shrink-0
                border-t border-white/10
                bg-[#0B7375]
                p-3
            "
        >

            <div
                class="
                    flex items-center gap-3
                    rounded-xl
                    px-2 py-2
                "
            >

                {{-- AVATAR --}}
                <div
                    class="
                        flex h-9 w-9
                        flex-shrink-0
                        items-center justify-center
                        rounded-full
                        bg-white
                        text-sm font-semibold
                        text-[#0F8B8D]
                    "
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>


                {{-- USER --}}
                <div class="min-w-0 flex-1">

                    <div
                        class="
                            truncate
                            text-sm font-medium
                            text-white
                        "
                    >
                        {{ auth()->user()->name }}
                    </div>

                    <div class="text-xs text-white/90">
                        {{ auth()->user()->role }}
                    </div>

                </div>


                {{-- LOGOUT --}}
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        title="Logout"
                        class="
                            flex h-8 w-8
                            items-center justify-center
                            rounded-lg
                            text-white/90
                            transition
                            hover:bg-white/10
                            hover:text-white
                        "
                    >

                        <x-icon name="log-out" class="h-[17px] w-[17px]" />

                    </button>

                </form>

            </div>

        </div>

    </aside>



    {{-- ============================================================
        MAIN AREA
    ============================================================ --}}

    <div class="min-w-0 flex-1">


        {{-- ========================================================
            TOPBAR
        ======================================================== --}}

        <header
            class="
                sticky top-0 z-20
                flex h-20
                items-center justify-between
                border-b border-slate-200
                border-t-2 border-t-[#32AAA6]/70
                bg-white
                px-8
            "
        >


            {{-- TITLE --}}
            <div>

                <h1
                    class="
                        text-xl
                        font-semibold
                        text-[#15235F]
                    "
                >
                    @yield('title', 'Dashboard')
                </h1>

                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-400
                    "
                >
                    Sistem Informasi Akuntansi CV Andika Mustika
                </p>

            </div>



            {{-- TOPBAR RIGHT --}}
            <div class="flex items-center gap-3">


                {{-- ACCOUNTING PERIOD --}}
                <div
                    class="
                        hidden
                        items-center gap-2
                        rounded-lg
                        border border-slate-200
                        bg-slate-50
                        px-3 py-2
                        md:flex
                    "
                >

                    <x-icon name="calendar-days" class="h-4 w-4 text-slate-400" />


                    <span
                        class="
                            text-xs
                            font-medium
                            text-slate-600
                        "
                    >
                        {{ now()->translatedFormat('F Y') }}
                    </span>

                </div>



                {{-- NOTIFICATION --}}
                <button
                    type="button"
                    title="Notifikasi"
                    class="
                        relative
                        flex h-10 w-10
                        items-center justify-center
                        rounded-lg
                        border border-slate-200
                        bg-white
                        text-slate-500
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <x-icon name="bell" class="h-[18px] w-[18px]" />


                    {{-- Notification indicator --}}
                    <span
                        class="
                            absolute
                            right-[9px] top-[8px]
                            h-2 w-2
                            rounded-full
                            bg-[#E6007E]
                        "
                    ></span>

                </button>

            </div>

        </header>



        {{-- ========================================================
            PAGE CONTENT
        ======================================================== --}}

        <main class="p-8">

            @yield('content')

        </main>

    </div>

</div>

</body>

</html>

