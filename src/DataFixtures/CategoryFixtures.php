<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'World',
            'Technology',
            'Design',
            'Culture',
            'Business',
            'Science',
            'Health',
            'Style',
            'Travel',
        ];

        foreach ($categories as $categoryTitle) {
            $category = new Category();
            $category->setTitle($categoryTitle);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
