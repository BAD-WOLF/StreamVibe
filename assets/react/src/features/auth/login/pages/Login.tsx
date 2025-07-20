import {type ReactElement, useState} from "react";
import AnimatedLogo from "@auth/login/components/AnimatedLogo";
import LoginForm from "@auth/login/components/LoginForm";
import LoginSuccess from "@auth/login/components/LoginSuccess";
import SocialLoginButtons from "@auth/login/components/SocialLoginButtons";

export default function Login(): ReactElement {
    const [isLoggedIn, setIsLoggedIn] = useState(false);

    const handleLoginSuccess = (): void => {
        setIsLoggedIn(true);
        // Simulates redirect with timeout
        setTimeout((): void => {
            window.location.href = "/home";
        }, 2000);
    };

    return (
        <div
            className="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1E3A5F] via-[#111827] to-[#000] px-6">
            <div
                className="max-w-md w-full space-y-8 bg-white/5 backdrop-blur-md p-8 rounded-xl shadow-xl border border-white/10">
                <AnimatedLogo/>
                {isLoggedIn ? (
                    <LoginSuccess/>
                ) : (
                    <>
                        <LoginForm onSubmit={handleLoginSuccess}/>
                        <div className="my-4 border-t border-white/10"/>
                        <SocialLoginButtons/>
                    </>
                )}
            </div>
        </div>
    );
}
