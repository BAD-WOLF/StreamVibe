import React from "react";
import { motion } from "framer-motion";
import { Movie } from "../../types/Movie";

export const MovieCard: React.FC<{ movie: Movie }> = ({movie}): JSX.Element => {
    return (
        <motion.div
            whileHover={{ scale: 1.05 }}
            className="relative rounded-3xl overflow-hidden shadow-2xl border border-[#5B21B6]/40 group"
        >
            <video
                src={movie.trailer}
                muted
                autoPlay
                loop
                playsInline
                className="absolute w-full h-full object-cover z-0 group-hover:opacity-100 opacity-0 transition duration-700"
            ></video>
            <img
                src={movie.poster}
                alt={movie.title}
                className="object-cover w-full h-96 opacity-90 group-hover:opacity-0 transition duration-500"
            />
            <div className="absolute bottom-0 w-full p-4 bg-gradient-to-t from-black/80 to-transparent z-10">
                <h3 className="text-xl font-bold text-white drop-shadow-md">
                    {movie.title}
                </h3>
                <p className="text-sm text-white/70">{movie.description}</p>
            </div>
        </motion.div>
    );
};