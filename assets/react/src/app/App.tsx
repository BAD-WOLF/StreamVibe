import "../shared/styles/globals.css";
import {type ReactElement, StrictMode} from "react";
import AppRoutes from "./AppRoutes.tsx";

export default function App(): ReactElement {
    return (
        <main className='h-lvh w-full'>
            <StrictMode>
                <AppRoutes/>
            </StrictMode>
        </main>
    )
}