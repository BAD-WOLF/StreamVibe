import React from 'react'
import {FaSearch} from 'react-icons/fa'

const Search = () => {
 const [query, setQuery]=React.useState()

 function getSearch(e){
  e.preventDefault() // no loading on submit
    if(query){
      window.location.href=`/movies/search/${query}/1`
    }
 }
  return (
    <section className="flex justify-center max-w-4xl mx-auto pt-4">

        {/* Form to submit movies, serie or actor to search */}
            <form className='relative flex justify-center items-center w-full' onSubmit={getSearch}>
                <input type='search' placeholder='Pesquisar por films, series ou nome de um autor' className='w-full py-2 px-6 pr-8 text-lg text-gray-600 font-light placeholder:text-base placeholder:text-gray-400 placeholder:font-light rounded-full' value={query} onChange={(e)=>setQuery(e.target.value)}/>
                
                <span className='absolute right-2 flex justify-center items-center'>
                        <button className='text-xl font-light text-ciano_escuro'> <FaSearch/> </button>
                </span>
            </form>

    </section>
  )
}

export default Search
