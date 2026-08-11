import './bootstrap';

import React from 'react';
import { createRoot } from 'react-dom/client';

import SuperAdmin from './pages/SuperAdmin';


const rootElement =
    document.getElementById('root');


if (rootElement) {

    createRoot(rootElement).render(
        <SuperAdmin />
    );
}