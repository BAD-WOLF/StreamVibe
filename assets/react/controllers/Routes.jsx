import React from 'react'
import {BrowserRouter,Routes, Route} from 'react-router'

//import components
import Header from './components/Header/Header'
import Home from './pages/Home'

export default function Index(){

    return(
        <>
          <BrowserRouter>
                <Header/>
            <Routes>
                <Route path='/' to={<Home/>}/>
            </Routes>
          </BrowserRouter>
        </>
    )
}