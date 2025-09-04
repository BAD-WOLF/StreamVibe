// Component displayed after successful login

import {AuthMode} from '@auth/login-register/types/AuthMode.ts'
import type {ReactElement} from "react";
import { t } from "i18next";
import { Trans } from 'react-i18next';

export default function AuthSuccess({authMode}: {authMode: AuthMode}): ReactElement {
    return (
        <div className="text-center py-20">
            <h2 className="text-3xl font-bold text-green-500">
                {(authMode !== AuthMode.Register) ? t("Login") : t("Registration")} <Trans>successful!</Trans></h2>
            <p className="text-white/80 mt-4"> <Trans>You will be redirected shortly...</Trans></p>
        </div>
    );
}
