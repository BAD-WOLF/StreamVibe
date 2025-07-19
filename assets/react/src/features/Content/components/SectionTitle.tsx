// Component for styled section title

import type {FC, ReactElement} from "react";

interface SectionTitleProps {
    title: string;
}

export const SectionTitle: FC<SectionTitleProps> = ({title}: SectionTitleProps): ReactElement => (
    <h2 className="text-4xl font-black mb-4 text-[#F0F4F8] tracking-wide drop-shadow-xl">
        {title}
    </h2>
);
