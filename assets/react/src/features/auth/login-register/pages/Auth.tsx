import {type Dispatch, type ReactElement, type SetStateAction, useState} from "react";
import AnimatedLogo from "@auth/login-register/components/AnimatedLogo.tsx";
import AuthForm from "@auth/login-register/components/AuthForm.tsx";
import AuthSuccess from "@auth/login-register/components/AuthSuccess.tsx";
import SocialLoginButtons from "@auth/login-register/components/SocialLoginButtons.tsx";
import { AuthMode } from "@auth/login-register/types/AuthMode.ts";

export default function AuthPage(): ReactElement {
    const [getAuthMode, setAuthMode]: (AuthMode | Dispatch<SetStateAction<AuthMode>>)[] = useState<AuthMode>(AuthMode.Login);
    const [isDone, setIsDone]: (boolean | Dispatch<SetStateAction<boolean>>)[] = useState(false);

    const handleSuccess: () => void = (): void => {
        setIsDone(true);
        if(getAuthMode !== AuthMode.Register) {
            setTimeout((): void => {
                window.location.href = "/home";
            }, 2000);
            return;
        }
        setTimeout((): void => {
            window.location.href = "/auth";
        }, 2000);
    };

    return (
        <div
            className="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1E3A5F] via-[#111827] to-[#000] px-6"
        >
            <div className="max-w-md w-full space-y-8 bg-white/5 backdrop-blur-md p-8 rounded-xl shadow-xl border border-white/10">
                <AnimatedLogo />
                {isDone ? (
                    <AuthSuccess authMode={getAuthMode} />
                ) : (
                    <>
                        <AuthForm onSubmit={handleSuccess} mode={getAuthMode} />
                        <div className="my-4 border-t border-white/10" />
                        <SocialLoginButtons />
                        <p className="text-sm text-center text-white/70 mt-4">
                            {getAuthMode === AuthMode.Login ? "Ainda não tem uma conta?" : "Já tem uma conta?"}{" "}
                            <button
                                className="text-cyan-400 underline"
                                onClick={(): void =>
                                    setAuthMode(getAuthMode === AuthMode.Login ? AuthMode.Register : AuthMode.Login)
                                }
                            >
                                {getAuthMode === AuthMode.Login ? "Crie uma agora" : "Entrar"}
                            </button>
                        </p>
                    </>
                )}
            </div>
        </div>
    );
}
