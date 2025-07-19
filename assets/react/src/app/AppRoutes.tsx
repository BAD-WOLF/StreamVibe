import type {ReactElement} from "react";
import {BrowserRouter, Route, Routes} from "react-router-dom";
import {Home} from "@content/home/pages/Home.tsx";

export default function AppRoutes(): ReactElement {
    /*const location = useLocation();

     const hideHeaderRoutes = ["/auth"];
     const shouldShowHeader = !hideHeaderRoutes.includes(location.pathname);*/

    return (
        <>
            {/*{shouldShowHeader && <Header/>}*/}
            <BrowserRouter>
                <Routes>
                    <Route path='/' element={<Home/>}/>
                </Routes>
            </BrowserRouter>
        </>
    );
}