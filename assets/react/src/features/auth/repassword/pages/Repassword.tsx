import EmailRequestForm from "@auth/repassword/components/EmailRequestForm";
import RepasswordSuccess from "@auth/repassword/components/RepasswordSuccess";
import ResetPasswordForm from "@auth/repassword/components/ResetPasswordForm";
import {useRepassword} from "@auth/repassword/hooks/useRepassword";

import {RepasswordMode, type RepasswordModeType} from "@auth/repassword/types/RepasswordMode";
import type {UseRepasswordReturn} from '@auth/repassword/types/UseRepasswordReturn.ts'
import {type ReactElement, useEffect} from "react";
import {useParams} from "react-router-dom";
import { Trans } from "react-i18next";

export default function Repassword(): ReactElement {
    const { locale: paramLocale, token: paramToken }: Readonly<Partial<{
        locale?: string | undefined;
        token?: string | undefined
    }>> = useParams<{ locale?: string; token?: string; }>();

    const locale: string = paramLocale ?? "pt_BR";

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
    }: UseRepasswordReturn = useRepassword((): void => {}, locale ?? "pt_BR", paramToken);

    // triggers validation automatically if there is a token in the URL
    useEffect((): void => {
        if (paramToken) {
            setToken(paramToken);
            handleValidate();
        }
    }, [paramToken]);

    // maps each mode to its content
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
                <Trans>
                    If there is an account with <b>{email}</b>, you will receive an email with the reset link.
                </Trans>

            </p>
        ),

        [RepasswordMode.Validate]: isLoading ? (
            <p className="text-center text-white"><Trans>Validating token…</Trans></p>
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

    // shows success if done, otherwise the current mode panel
    const content: ReactElement | null = isDone ? <RepasswordSuccess/> : panels[mode];

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-900 px-4">
            <div className="w-full max-w-md p-8 bg-white/10 rounded-lg shadow-lg space-y-6">
                {content}
            </div>
        </div>
    );
}
