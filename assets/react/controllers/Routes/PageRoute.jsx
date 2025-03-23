import React from 'react'
import {BrowserRouter,Routes, Route} from 'react-router'

//import components
import Header from '../components/Header/Header'
import Footer from '../components/Footer/Footer'
import Home from '../pages/Home/Home'
import Button from '../components/Forms/Button'


export default function Index(){

    return(
        <>
          <BrowserRouter>
            <Header/>

            <Routes>
                <Route exact path='/' element={<Home/>} />
            </Routes>

            <Footer/>
          </BrowserRouter>
        </>
    )
}