import type {Movie} from "@content/types/movie";
import {preloadVideo} from "@content/utils/videoUtils";
import {motion} from "framer-motion";
import type {FC, ReactElement, RefObject} from "react";
import {useEffect, useRef} from "react";

export const MovieCard: FC<{ movie: Movie }> = ({movie}: { movie: Movie }): ReactElement => {
    const videoRef: RefObject<HTMLVideoElement | null> = useRef<HTMLVideoElement>(null);

    useEffect((): void => {
        // Preloads trailer
        preloadVideo(movie.trailer);
    }, [movie.trailer]);

    return (
        <motion.div whileHover={{scale: 1.05}}
                    className="relative rounded-3xl overflow-hidden shadow-2xl border border-[#5B21B6]/40 group">
            <video
                ref={videoRef}
                src={movie.trailer}
                muted
                autoPlay
                loop
                playsInline
                className="
                    absolute w-full h-full object-cover z-0 group-hover:opacity-100
                    opacity-0 transition duration-700"
            />
            <img
                src={movie.poster}
                alt={movie.title}
                className="object-cover w-full h-96 opacity-90 group-hover:opacity-0 transition duration-500"
            />
            <div className="absolute bottom-0 w-full p-4 bg-gradient-to-t from-black/80 to-transparent z-10">
                <h3 className="text-xl font-bold text-white drop-shadow-md">{movie.title}</h3>
                <p className="text-sm text-white/70">{movie.description}</p>
            </div>
        </motion.div>
    );
};
