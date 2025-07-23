// Component displayed after successful login

import type {ReactElement} from "react";

export default function LoginSuccess(): ReactElement {
    return (
        <div className="text-center py-20">
            <h2 className="text-3xl font-bold text-green-500">Login realizado com sucesso!</h2>
            <p className="text-white/80 mt-4">Você será redirecionado em instantes...</p>
        </div>
    );
}
