import AnimatedLogo from "@auth/login-register/components/AnimatedLogo.tsx";
import AuthForm from "@auth/login-register/components/AuthForm.tsx";
import LoginSuccess from "@auth/login-register/components/LoginSuccess.tsx";
import SocialLoginButtons from "@auth/login-register/components/SocialLoginButtons.tsx";
import type {AuthMode} from "@auth/login-register/types/AuthMode.ts";
import {type ReactElement, useState} from "react";

export default function AuthPage(): ReactElement {
    const [mode, setMode] = useState<AuthMode>("login");
    const [isDone, setIsDone] = useState(false);

    const handleSuccess = () => {
        setIsDone(true);
        setTimeout(() => {
            window.location.href = "/home";
        }, 2000);
    };

    return (
        <div
            className="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1E3A5F] via-[#111827] to-[#000] px-6">
            <div
                className="max-w-md w-full space-y-8 bg-white/5 backdrop-blur-md p-8 rounded-xl shadow-xl border border-white/10">
                <AnimatedLogo/>
                {isDone ? (
                    <LoginSuccess/>
                ) : (
                    <>
                        <AuthForm onSubmit={handleSuccess} mode={mode}/>
                        <div className="my-4 border-t border-white/10"/>
                        <SocialLoginButtons/>
                        <p className="text-sm text-center text-white/70 mt-4">
                            {mode === "login" ? "Ainda não tem uma conta?" : "Já tem uma conta?"}{" "}
                            <button className="text-cyan-400 underline"
                                    onClick={() => setMode(mode === "login" ? "register" : "login")}>
                                {mode === "login" ? "Crie uma agora" : "Entrar"}
                            </button>
                        </p>
                    </>
                )}
            </div>
        </div>
    );
}
