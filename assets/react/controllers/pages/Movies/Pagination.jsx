import React from "react";
import { GrNext } from "react-icons/gr";
import { GrPrevious } from "react-icons/gr";



const Pagination = ({ totalPages, currentPage, onPageChange, query }) => {
  
  const maxVisiblePages = 5; // Número máximo de botões visíveis
  const startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
  const endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
 
  function handleNextPage(){
    if(currentPage < totalPages){
      const nextPage=currentPage + 1
      onPageChange(nextPage)
      window.location.href=`/movies/show/${query}/${nextPage}`
    }
  }

  function handlePreviousPage(){
    if(currentPage > 1){
      const previousPage=currentPage - 1
      onPageChange(previousPage)
      window.location.href=`/movies/show/${query}/${previousPage}`
    }
  }

  return (
    <section className="flex justify-center my-4">
      <button
        onClick={handlePreviousPage}
        disabled={currentPage === 1}
        className="px-4 py-2 rounded-l-md"
      >
        <GrPrevious/>
      </button>

      {[...Array(endPage - startPage + 1)].map((_, index) => {
        const pageNumber = startPage + index; // Calcula o número da página visível
        
        return (
          <button
            key={pageNumber}
            onClick={() => {
              onPageChange(pageNumber); // Atualiza o estado da página atual
              window.location.href = `/movies/show/${query}/${pageNumber}`; // Redireciona para a URL correspondente
            }}
            className={`px-2 text-gray-200 font-light ${
              currentPage === pageNumber ? "bg-gray-600 text-gray-200" : "text-gray-400"
            }`}
          >
      {pageNumber}
    </button>
  );
})}

      <button
        onClick={handleNextPage}
        disabled={currentPage === totalPages}
        className="px-4 py-2 rounded-r-md">
        <GrNext/>
      </button>
    
    </section>
  );
};

export default Pagination;