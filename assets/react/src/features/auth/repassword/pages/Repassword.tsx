import EmailRequestForm from "@auth/repassword/components/EmailRequestForm";
import RepasswordSuccess from "@auth/repassword/components/RepasswordSuccess";
import ResetPasswordForm from "@auth/repassword/components/ResetPasswordForm";
import {useRepassword} from "@auth/repassword/hooks/useRepassword";

import {RepasswordMode, type RepasswordModeType} from "@auth/repassword/types/RepasswordMode";
import type {UseRepasswordReturn} from '@auth/repassword/types/UseRepasswordReturn.ts'
import {type ReactElement, useEffect} from "react";
import {useParams} from "react-router-dom";

export default function Repassword(): ReactElement {
    const {token: paramToken}: Readonly<Partial<{ token?: string | undefined }>> = useParams<{ token?: string }>();
    const {
        mode,
        isRequested,
        isDone,
        email,
        password,
        strength,
        confirmPassword,
        errors,
        isLoading,
        showPassword,
        setShowPassword,
        setToken,
        setEmail,
        setPassword,
        setConfirmPassword,
        handleRequest,
        handleValidate,
        handleReset,
    }: UseRepasswordReturn = useRepassword((): void => {
    }, paramToken);

    // dispara validação automaticamente se houver token na URL
    useEffect((): void => {
        if (paramToken) {
            setToken(paramToken);
            handleValidate();
        }
    }, [paramToken]);

    // mapeia cada modo ao seu conteúdo
    const panels: Record<RepasswordModeType, ReactElement | null> = {
        [RepasswordMode.Request]: !isRequested ? (
            <EmailRequestForm
                email={email}
                errors={errors}
                isLoading={isLoading}
                setEmail={setEmail}
                handleRequest={handleRequest}
            />
        ) : (
            <p className="text-center text-white/80">
                Se existir conta em <b>{email}</b>, você receberá um email com o link
                de redefinição.
            </p>
        ),

        [RepasswordMode.Validate]: isLoading ? (
            <p className="text-center text-white">Validando token…</p>
        ) : errors.token ? (
            <p className="text-red-500 text-center">{errors.token}</p>
        ) : null,

        [RepasswordMode.Reset]: (
            <>
                <ResetPasswordForm
                    password={password}
                    showPassword={showPassword}
                    setShowPassword={setShowPassword}
                    strength={strength}
                    confirmPassword={confirmPassword}
                    errors={errors}
                    isLoading={isLoading}
                    setPassword={setPassword}
                    setConfirmPassword={setConfirmPassword}
                    handleReset={handleReset}
                />

            </>
        ),
    };

    // se finalizado, mostra sucesso; senão, o painel do modo atual
    const content: ReactElement | null = isDone ? <RepasswordSuccess/> : panels[mode];

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-900 px-4">
            <div className="w-full max-w-md p-8 bg-white/10 rounded-lg shadow-lg space-y-6">
                {content}
            </div>
        </div>
    );
}
