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

