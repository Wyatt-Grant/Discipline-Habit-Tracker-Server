import { Link, Head } from '@inertiajs/react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-gray-900">
                <div className="sm:fixed sm:top-0 sm:right-0 p-6 text-right">
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={route('login')}
                                className="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                            >
                                Log in
                            </Link>

                            {/* <Link
                                href={route('register')}
                                className="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                            >
                                Register
                            </Link> */}
                        </>
                    )}
                </div>

                <div className="max-w-7xl mx-auto p-6 lg:p-8">
                    <div className="flex justify-center">
                    <h1 class="text-6xl text-white">Discipline &nbsp;</h1>
                        <svg width="1000" height="1000" viewBox="0 0 1000 1000"
                            className="h-16 w-auto bg-gray-100 dark:bg-gray-900"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink">
                            <g transform="translate(20,950) scale(1,-1)" stroke="none">
                                <circle cx="480" cy="450"  r="480"/>
                                <circle cx="480" cy="450" fill="#ffffff" r="432.5"/>
                                <path stroke="#000000" stroke-width="12.5" stroke-miterlimit="12" d="M99.2305,450
                                            C99.2305,359.208,139.343,172.874,299.804,172.874
                                            C398.972,172.874,473.232,259.467,473.232,346.322
                                            C473.232,369.911,468.406,393.757,458.352,416.352
                                            C314.279,429.096,237.699,561.161,237.699,657.699
                                            C237.699,694.85,246.056,730.054,261.004,761.518
                                            C163.142,692.605,99.2305,578.767,99.2305,450Z
                                            M319.587,104.562
                                            C377.722,77.4922,431.884,69.2305,480,69.2305
                                            C691.986,69.2305,833.312,235.411,833.312,346.201
                                            C833.312,433.483,765.104,519.278,660.697,519.278
                                            C602.287,519.278,551.648,491.685,519.955,448.087
                                            C535.175,415.371,542.471,380.682,542.471,346.371
                                            C542.471,228.807,453.173,115.212,319.587,104.562Z
                                            M306.938,657.692
                                            C306.938,568.193,374.87,494.561,461.99,485.542
                                            C508.158,551.824,583.156,588.509,660.095,588.509
                                            C739.771,588.509,814.031,549.485,859.303,483.892
                                            C842.13,678.319,678.876,830.77,480.014,830.77
                                            C386.641,830.77,306.938,757.32,306.938,657.692Z"/>
                                <g fill="#ffffff">
                                    <circle cx="465.012" cy="671.553" r="38"/>
                                    <circle cx="678.8935" cy="352.607" r="38"/>
                                    <circle cx="295.8735" cy="326.5335" r="38"/>
                                </g>
                            </g>
                        </svg>

                    </div>

                    <div className="flex justify-center sm:items-center sm:justify-between">
                        <div className="ml-4 text-center text-sm text-gray-500 dark:text-gray-400 sm:text-right sm:ml-0">
                            Built with laravel v{laravelVersion}
                        </div>
                    </div>
                </div>
            </div>

            <style>{`
                .bg-dots-darker {
                    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
                }
                @media (prefers-color-scheme: dark) {
                    .dark\\:bg-dots-lighter {
                        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
                    }
                }
            `}</style>
        </>
    );
}
