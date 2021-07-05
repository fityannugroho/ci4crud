<?php

namespace App\Controllers;

use \App\Models\BookModel;
use DateTimeZone;

class Books extends BaseController
{
    protected $bookModel;


    public function __construct()
    {
        $this->bookModel = new BookModel();
    }


    public function index()
    {
        $books = $this->bookModel->getBook();

        $data = [
            'title' => 'My Books',
            'books' => $books
        ];

        return view('books/index', $data);
    }


    public function details($slug)
    {
        $book = $this->bookModel->getBook($slug, 'slug');

        // checking the existence of the book
        if (empty($book)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('The book is not found');
        }

        $data = [
            'title' => $book['title'],
            'book' => $book
        ];

        return view('books/details', $data);
    }


    public function add()
    {
        $data = [
            'title' => 'Add Book',
            'validation' => \Config\Services::validation()
        ];

        return view('books/add', $data);
    }


    public function insert()
    {
        // validation rules to insert a new book
        $insertRules = $this->bookModel->getDefaultRules();

        // form validation
        if (!$this->validate($insertRules)) {
            // $validation = \Config\Services::validation();
            // return redirect()->to('/books/add')->withInput()->with('validation', $validation);

            // return to the form page with the form data and validation results
            return redirect()->to('/books/add')->withInput();
        }

        // take the book cover image file
        $cover = $this->request->getFile('cover');

        // check if there is no file uploaded
        $noFileUploadedErrCode = 4;
        if ($cover->getError() === $noFileUploadedErrCode) {
            // set the cover image to default cover image
            $newCoverName = 'default-cover.jpg';
        } else {
            // generate a random name for $cover file
            $newCoverName = $cover->getRandomName();

            // move $cover to server-storage folder with new name
            $cover->move('assets/images', $newCoverName);
        }

        // make the slug of the book title
        $slug = url_title($this->request->getVar('title'), '-', true);

        // insert data
        $this->bookModel->save([
            'title' => $this->request->getVar('title'),
            'slug' => $slug,
            'writer' => $this->request->getVar('writer'),
            'publisher' => $this->request->getVar('publisher'),
            'cover' => $newCoverName
        ]);

        // set success alert with session
        session()->setFlashdata('message', 'Book successfully added.');

        return redirect()->to('/books');
    }


    public function delete($id)
    {
        $this->bookModel->delete($id);

        // set success alert with session
        session()->setFlashdata('message', 'Book successfully deleted.');

        return redirect()->to('/books');
    }


    public function edit($slug)
    {
        $data = [
            'title' => 'Edit Book',
            'validation' => \Config\Services::validation(),
            'book' => $this->bookModel->getBook($slug, 'slug')
        ];

        return view('books/edit', $data);
    }


    public function update($id)
    {
        // validation rules to update a book
        $updateRules = $this->bookModel->getDefaultRules();
        $updateRules['title']['rules'] = "required|max_length[255]|is_unique[book.title,id,$id]";

        // form validation
        if (!$this->validate($updateRules)) {
            // return to the form page with the form data and validation results
            $oldBook = $this->bookModel->getBook($id);
            return redirect()->to("/books/edit/$oldBook[slug]")->withInput();
        }

        // make the new slug of the book title
        $slug = url_title($this->request->getVar('title'), '-', true);

        // updating the book
        $this->bookModel->save([
            'id' => $id,
            'title' => $this->request->getVar('title'),
            'slug' => $slug,
            'writer' => $this->request->getVar('writer'),
            'publisher' => $this->request->getVar('publisher'),
            'cover' => $this->request->getVar('cover')
        ]);

        // set success alert with session
        session()->setFlashdata('message', 'Book successfully edited.');

        return redirect()->to("/books/$slug");
    }
}
