import Auth from "@auth/login-register/pages/Auth.tsx";
import Repassword from '@auth/repassword/pages/Repassword.tsx'
import {Home} from "@content/home/pages/Home.tsx";
import type {ReactElement} from "react";
import {BrowserRouter, Route, Routes} from "react-router-dom";

export default function AppRoutes(): ReactElement {
    /*const location = useLocation();

     const hideHeaderRoutes = ["/auth"];
     const shouldShowHeader = !hideHeaderRoutes.includes(location.pathname);*/

    return (
        <>
            {/*{shouldShowHeader && <Header/>}*/}
            <BrowserRouter>
                <Routes>
                    <Route path='/:_locale?/home' element={<Home/>}/>
                    <Route path='/:_locale?/auth' element={<Auth/>}/>
                    <Route path="/:_locale?/auth/repassword/:token?" element={<Repassword/>}/>
                </Routes>
            </BrowserRouter>
        </>
    );
}