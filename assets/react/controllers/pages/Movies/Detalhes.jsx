import React from "react";

// Import components
import Header from "../../components/Header/Header";
import Footer from "../../components/Footer/Footer";

const MovieDetails = ({ movieDetails }) => {
  const [details, setDetails] = React.useState([]);
  const [loading, setLoading] = React.useState(true);

  const showMovieDetails = React.useCallback(() => {

    setLoading(true);
 
    try {

      if (Array.isArray(movieDetails)) {
        setDetails(movieDetails); // Se for um array, usa diretamente
      } else if (movieDetails) {
        setDetails([movieDetails]); // Transforma em array se for um objeto

      } else {
        setDetails([]); // Define como array vazio se for undefined ou null
      }
    } catch (err) {
      console.log(err);
    } finally {
      setLoading(false);
    }

  }, [movieDetails]);

  React.useEffect(() => {
    showMovieDetails();
  }, [showMovieDetails]);

  React.useEffect(() => {
    console.log(details);
  }, [details]);

  return (
    <>
      <Header />
      <main className="min-h-screen w-full">

        {loading ? (
          <div>Loading...</div>
        ) : Array.isArray(details) && details.length > 0 ? (
          <section className="w-full h-full flex flex-col gap-4">

            {details.map((detail, index) => {

             return ( // Adicionando index como key para evitar erro de chave única

                  <div key={index} className=" relative w-full h-full bg-cover bg-center p-4" 
                  style={{ backgroundImage: `url(https://image.tmdb.org/t/p/w500${
                    detail.backdrop_path || detail.poster_path })`,
                  }}>
                
                {/* Add Delay */}
                  <div className="absolute bg-gray-900/90 inset-0 z-0"></div>

                  <div className="flex flex-col md:flex-row items-start gap-4">
                    <img
                      src={`https://image.tmdb.org/t/p/w500${
                        detail.poster_path || detail.backdrop_path
                      }`}
                      alt={detail.title}
                      style={{ width: "20rem" }}
                      className="rounded-lg shadow-lg z-10"
                    />
                    
                    <div className="text-gray-200 z-10 border">
                      <h2 className="text-2xl font-medium mb-2">
                        {detail.title}
                      </h2>
                      <p className="mb-4">{detail.overview}</p>
                      <p className="mb-2">
                        <strong>Lançamento:</strong> {detail.release_date}
                      </p>
                      <p>
                        <strong>Avaliação:</strong> {detail.vote_average}
                      </p>
                    </div>

                  </div>

               </div>
               )
            })}
            
          </section>
        ) : (
          <div>Erro ao carregar detalhes</div>
        )}
      </main>
      <Footer />
    </>
  );
};

export default MovieDetails;
