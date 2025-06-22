import { type FC, type JSX } from "react";
import { motion } from "framer-motion";

export const CategoriesSection: FC = (): JSX.Element => {
    const sectionTitles: string[] = ["Trending Now", "New Releases", "Sci-Fi Realms"];

    return (
        <section className="px-6 py-20 space-y-24">
            {sectionTitles.map((sectionTitle: string): JSX.Element => (
                <div key={sectionTitle} className="space-y-6">
                    <h2 className="text-4xl font-black mb-4 text-[#F0F4F8] tracking-wide drop-shadow-xl">
                        {sectionTitle}
                    </h2>
                    <div className="flex overflow-x-auto overflow-y-hidden gap-6 pb-2">
                        {[...Array(10)].map((_: unknown, idx: number): JSX.Element => (
                            <motion.div
                                key={idx}
                                whileHover={{ scale: 1.1, rotate: -1 }}
                                className="min-w-[220px] h-[330px] rounded-xl overflow-hidden bg-[#1E3A5F] border border-[#9B111E]/40 shadow-[0_0_20px_#5B21B6] relative"
                            >
                                <div className="w-full h-full bg-gradient-to-br from-[#5B21B6]/40 to-[#9B111E]/20 flex items-center justify-center text-center text-white/90">
                                    Movie {idx + 1}
                                </div>
                                <div className="absolute inset-0 bg-black/30 backdrop-blur-sm opacity-0 hover:opacity-100 transition-all duration-300 flex items-center justify-center text-sm text-white">
                                    View Details
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>
            ))}
        </section>
    );
};