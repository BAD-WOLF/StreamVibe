// Component displayed after successful login

import {AuthMode} from '@auth/login-register/types/AuthMode.ts'
import type {ReactElement} from "react";

export default function AuthSuccess({authMode}: {authMode: AuthMode}): ReactElement {
    return (
        <div className="text-center py-20">
            <h2 className="text-3xl font-bold text-green-500">
                {(authMode !== AuthMode.Register) ? "Login" : "Register"} realizado com sucesso!</h2>
            <p className="text-white/80 mt-4">Você será redirecionado em instantes...</p>
        </div>
    );
}
