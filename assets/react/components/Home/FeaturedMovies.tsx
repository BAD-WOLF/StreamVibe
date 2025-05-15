import { FC } from "react";
import { Movie } from "../../types/Movie";
import { MovieCard } from "./MovieCard";

export const FeaturedMovies: FC<{movies: Movie[]}> = ({movies}): JSX.Element => {
    return (
        <section className="relative h-[90vh] flex items-center justify-center">
            <div className="relative z-10 w-full px-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                {movies.map((movie: Movie) => (
                    <MovieCard key={movie.title} movie={movie} />
                ))}
            </div>
        </section>
    );
};