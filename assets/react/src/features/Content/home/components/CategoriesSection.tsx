import type {FC, ReactElement} from "react";
import {motion} from "framer-motion";
import {SectionTitle} from "@content/components/SectionTitle";
import {MovieGrid} from "@content/components/MovieGrid";
import { Trans } from 'react-i18next';
import { t } from 'i18next';

const sectionTitles: string[] = [
    t("Trending Now"), 
    t("New Releases"), 
    t("Sci‑Fi Realms")
];

export const CategoriesSection: FC = (): ReactElement => (
    <section className="px-6 py-20 space-y-24">
        {sectionTitles.map((title: string): ReactElement => (
            <div key={title} className="space-y-6">
                <SectionTitle title={title}/>
                <MovieGrid>
                    {[...Array(10)].map((_: unknown, idx: number): ReactElement => (
                        <motion.div
                            key={idx}
                            whileHover={{scale: 1.1, rotate: -1}}
                            className="min-w-[220px] h-[330px] rounded-xl overflow-hidden bg-[#1E3A5F] border border-[#9B111E]/40 shadow-[0_0_20px_#5B21B6] relative"
                        >
                            <div
                                className="w-full h-full bg-gradient-to-br from-[#5B21B6]/40 to-[#9B111E]/20 flex items-center justify-center text-center text-white/90">
                                <Trans>Movie</Trans> {idx + 1}
                            </div>
                            <div
                                className="absolute inset-0 bg-black/30 backdrop-blur-sm opacity-0 hover:opacity-100 transition-all duration-300 flex items-center justify-center text-sm text-white">
                                <Trans>View Details</Trans>
                            </div>
                        </motion.div>
                    ))}
                </MovieGrid>
            </div>
        ))}
    </section>
);
