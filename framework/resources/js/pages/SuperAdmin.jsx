import { useEffect, useState } from 'react';

function SuperAdmin() {

    // ==========================================
    // USERS FROM DATABASE
    // ==========================================

    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    // ==========================================
    // LOAD USERS FROM LARAVEL
    // ==========================================
    useEffect(() => {

        fetch('/api/super-admin/users')

            .then((response) => {

                if (!response.ok) {
                    throw new Error(
                        'Unable to load users.'
                    );
                }

                return response.json();
            })

            .then((data) => {

                if (data.success) {

                    setUsers(data.users);

                } else {

                    setError(
                        'Unable to retrieve users.'
                    );
                }

            })

            .catch((error) => {

                console.error(error);

                setError(
                    'An error occurred while loading users.'
                );

            })

            .finally(() => {

                setLoading(false);

            });

    }, []);


    // ==========================================
    // PAGE
    // ==========================================

    return (

          <div className="min-h-screen bg-gray-100 p-10">

            <h1 className="text-3xl font-bold text-blue-700">
                Super Admin Dashboard
            </h1>

            <p className="mt-3 text-gray-600">
                WalangBrownout Framework Version
            </p>


            {/* ==================================
                USERS
            ================================== */}

            <div className="mt-8 bg-white rounded-lg shadow p-6">

                <h2 className="text-xl font-bold text-gray-800 mb-4">
                    System Users
                </h2>


                {/* LOADING */}

                {loading && (

                    <p className="text-gray-600">
                        Loading users...
                    </p>

                )}


                {/* ERROR */}

                {error && (

                    <p className="text-red-600">
                        {error}
                    </p>

                )}


                {/* USER TABLE */}

                {!loading && !error && (

                    <div className="overflow-x-auto">

                        <table className="w-full border-collapse">

                            <thead>

                                <tr className="bg-gray-200 text-left">

                                    <th className="p-3">
                                        ID
                                    </th>

                                    <th className="p-3">
                                        Name
                                    </th>

                                    <th className="p-3">
                                        Email
                                    </th>

                                    <th className="p-3">
                                        Contact
                                    </th>

                                    <th className="p-3">
                                        Role
                                    </th>

                                    <th className="p-3">
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                {users.map((user) => (

                                    <tr
                                        key={user.user_id}
                                        className="border-b"
                                    >

                                        <td className="p-3">
                                            {user.user_id}
                                        </td>

                                        <td className="p-3">
                                            {user.name}
                                        </td>

                                        <td className="p-3">
                                            {user.email}
                                        </td>

                                        <td className="p-3">
                                            {user.contact_number || 'N/A'}
                                        </td>

                                        <td className="p-3">
                                            {user.role}
                                        </td>

                                        <td className="p-3">
                                            {user.account_status}
                                        </td>

                                    </tr>

                                ))}

                            </tbody>

                        </table>

                    </div>

                )}

            </div>

        </div>
    );
}

export default SuperAdmin;



