<?php

namespace Model\UseCase;

use Model\Persistence\DateRepositoryPDO;

class ManageDateUseCase
{

    private DateRepositoryPDO $dateRepo;
    public function __construct()
    {
        $this->dateRepo = new DateRepositoryPDO();
    }

    public function createDate(string $id, string $titre, string $description, string $date, string $type)
    {
        $this->dateRepo->create($id, $titre, $description, $date, $type);
    }

    public function deleteDate(string $id)
    {
        $this->dateRepo->delete($id);
    }

    public function updateDate(string $id, string $titre, string $description, string $date, string $type)
    {
        $this->dateRepo->update($id, $titre, $description, $date, $type);
    }

    public function showDatesFromDay(string $date){
        return $this->dateRepo->findbyDate($date);
    }

    public function getDateFromId(string $id){
        return $this->dateRepo->findById($id)->getDate();
    }
}