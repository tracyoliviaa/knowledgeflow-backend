// src/Repository/ItemRepository.php

public function search(string $query, User $user): array
{
    return $this->createQueryBuilder('i')
        ->where('i.user = :user')
        ->andWhere(
            'MATCH(i.title, i.content) AGAINST (:query IN BOOLEAN MODE) > 0'
        )
        ->setParameter('user', $user)
        ->setParameter('query', $query)
        ->orderBy('i.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}