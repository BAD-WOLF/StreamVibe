import React from 'react'
import {BrowserRouter,Routes, Route} from 'react-router'

//import components
import Header from './components/Header/Header'
import Home from './pages/Home/Home'

export default function Index(){

    return(
        <>
          <BrowserRouter>

            <Header/>
            <Routes>
                <Route exact path='/' element={<Home/>} />
            </Routes>

          </BrowserRouter>
        </>
    )
}