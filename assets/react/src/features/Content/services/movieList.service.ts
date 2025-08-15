import type {Movie} from "@content/types/movie";

/**
 * Returns the list of mocked movies or via fetch for FeaturedMovies
 */
export function getMovieList(): Movie[] {
    return [
        {
            title: "Nebula Rising",
            description: "A cosmic tale of rebellion in the Andromeda system.",
            poster: "https://image.tmdb.org/t/p/w500/nebula.jpg",
            trailer: "https://www.w3schools.com/html/mov_bbb.mp4",
        },
        {
            title: "Quantum Drift",
            description: "An astronaut lost in the multiverse must find his way home.",
            poster: "https://image.tmdb.org/t/p/w500/quantum.jpg",
            trailer: "https://www.w3schools.com/html/movie.mp4",
        },
        {
            title: "The Last Fractal",
            description: "A hacker discovers the code that shapes reality itself.",
            poster: "https://image.tmdb.org/t/p/w500/fractal.jpg",
            trailer: "https://www.w3schools.com/html/mov_bbb.mp4",
        },
    ];
}
