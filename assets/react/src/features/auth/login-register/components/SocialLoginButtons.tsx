// Buttons for social login (Google, GitHub, etc.)

import type {Icon} from "@shared/types/Icon.ts";
import type {ReactElement} from "react";
import {FaGithub, FaGoogle} from "react-icons/fa";
import { Trans } from 'react-i18next';

export default function SocialLoginButtons(): ReactElement {
    const providers: Array<Icon> = [
        {name: "Google", icon: <FaGoogle/>, color: "bg-red-600"},
        {name: "GitHub", icon: <FaGithub/>, color: "bg-gray-800"},
    ];

    return (
        <div className="flex flex-col gap-3">
            {providers.map(({name, icon, color}: Icon): ReactElement => (
                <button
                    key={name}
                    type="button"
                    className={`flex items-center justify-center gap-3 px-4 py-2 rounded-md ${color} text-white shadow-md hover:opacity-90 transition`}
                >
                    {icon}
                    <Trans>Log in with</Trans> {name}
                </button>
            ))}
        </div>
    );
}
