import React from 'react'

//Import components
import Header from '../../components/Header/Header'
import Footer from '../../components/Footer/Footer'
import Banner from '../Home/Banner'
import Pagination from './Pagination'

const Movies = ({movies}) => {
  //Obtendo a query da url
  const urlParts = window.location.pathname.split("/");
  const query = decodeURIComponent(urlParts[3]);
  const page = urlParts[4];
  const totalPages=movies.total_pages

  const [movie, setMovie] = React.useState([])
  const [loading, setLoading]=React.useState(true)
  const [currentPage, setCurrentPage]=React.useState(Number(page)); //página atual

  //funcao para chamar filmes, memorizada
  const fetchMovies=React.useCallback(()=>{
    setLoading(true) //define o estado de carregamento

    try{
      setMovie(movies.results) //colocando a lista de filmes no estado

    } catch(err){
      console.error(err) //exibe um erro caso a tentativa falhar

    } finally{
      setLoading(false) // para com o carregando
    }

  }, [movies])

 //chamar a funcao memorizada
  React.useEffect(()=>{
    fetchMovies()
  },[fetchMovies])
  
  return (
    <>
    <Header/>
    <main className='min-h-screen w-full '>

      {/* Show Banner */}
      <section className=''>
        <Banner/>
      </section>

      <section className='Container md:px-4 px-10'>

          <section className='text-gray-200 text-2xl py-8 pb-4'>
            <h3>Resultados de {query}</h3>
          </section>

          <section className='Container flex flex-col justify-center gap-6'>

              {/* Show Data */}
              {movie.length > 0 ? movie.map((movie) => {
                //verifica se o filme tem poster ou backdrop
                
                if (!movie.poster_path && !movie.backdrop_path){
                  return null
                }
                
                //formata a data em dd de mm de yy
                const formatedDate=movie.release_date?
                new Intl.DateTimeFormat('pt-BR', { 
                  day: 'numeric', 
                  month: 'long', 
                  year: 'numeric' 
                }).format(new Date(movie.release_date)):'Data não disponível';

                return (
                  <div key={movie.id} >
                      <a href={`/movies/details/${movie.id}`} className='flex justify-start rounded-sm border border-gray-900 shadow-sm shadow-gray-800'>
                        <img src={`https://image.tmdb.org/t/p/w500${movie.poster_path || movie.backdrop_path}`} alt={movie.title} style={{width:'8rem'}}/>
                        
                        <div className='mx-4 flex flex-col justify-between py-4'>
                          
                          <div>
                                <h3 className='font-light text-gray-300 text-base'>{movie.title}</h3>
                                <span className='font-light text-gray-500 text-sm'> {formatedDate}</span>
                          </div>

                          <p className='font-light text-gray-400 text-justify text-sm'>{movie.overview}</p>
                      </div>
                      </a>
                  </div>
                )
                
              }) : <h3 className='text-center text-gray-200 text-2xl'>Nenhum filme encontrado</h3>}
          
          </section>

          <section className='Container py-8 flex justify-center items-center'>
            
            <div>
                <Pagination totalPages={totalPages} currentPage={currentPage} onPageChange={setCurrentPage} query={query}/>
            </div>
          </section>

    </section>


    </main>
    <Footer/>
    </>
  )
}

export default Movies
