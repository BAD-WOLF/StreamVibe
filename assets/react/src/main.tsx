import {StrictMode} from 'react';
import {createRoot} from 'react-dom/client';
import './shared/styles/globals.css';
import App from './app/App.tsx';
import '@i18n/i18n';

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <App/>
    </StrictMode>
);
