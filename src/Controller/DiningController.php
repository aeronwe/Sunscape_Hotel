<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dining')]
final class DiningController extends AbstractController
{
    #[Route('', name: 'dining_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dining/index.html.twig', [
            'venues' => [
                ['name' => 'El Juwan Restaurant', 'slug' => 'el-juwan', 'tag' => 'Filipino', 'hours' => '6:00 AM - 10:00 PM', 'image' => 'images/island_kitchen.jpg', 'description' => 'Our signature restaurant serving locally sourced seafood and other Filipino cuisine.'],
                ['name' => 'The Island Bar', 'slug' => 'island-bar', 'tag' => 'Light Bites & Cocktails', 'hours' => '8:00 AM - 12:00 AM', 'image' => 'images/island_bar.jpg', 'description' => 'Refreshing drinks and snacks by the sea.'],
                ['name' => 'Coast Grill Nights', 'slug' => 'coast-grill', 'tag' => 'Grilled Seafood & Filipino BBQ', 'hours' => '5:00 PM - 4:00 AM', 'image' => 'images/Beach-BBQ-9.jpg', 'description' => 'Open air dining under the stars.'],
            ],
        ]);
    }

    #[Route('/{id}', name: 'dining_detail', methods: ['GET'])]
    public function detail(string $id): Response
    {
        if ($id === 'beach-bbq' || $id === 'beach-bbq-nights') {
            $id = 'coast-grill';
        }

        $allVenues = $this->getVenues();

        if (!isset($allVenues[$id])) {
            throw $this->createNotFoundException('Dining venue not found.');
        }

        $venue = $allVenues[$id];

        return $this->render('dining/detail.html.twig', [
            'venue'      => $venue,
            'slug'       => $id,
            'allVenues'  => $allVenues,
        ]);
    }

    private function getVenues(): array
    {
        return [
            'el-juwan' => [
                'slug'        => 'el-juwan',
                'name'        => 'El Juwan Restaurant',
                'tag'         => 'Filipino',
                'badge_class' => 'suite',
                'hours'       => '6:00 AM - 10:00 PM',
                'subtitle'    => 'Our Signature Beachfront Dining',
                'description' => 'Our signature restaurant serving locally sourced seafood and other Filipino cuisine. Enjoy fresh island flavors and warm hospitality right beside the beach.',
                'location'    => 'Beachfront Pavilion, Main Resort Level',
                'seating'     => 'Indoor Dining and Seaside Terrace',
                'price_range' => '₱300 - ₱700 per guest',
                'images'      => [
                    'images/island_kitchen.jpg',
                    'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1000&q=80',
                ],
                'highlights' => [
                    'Fresh Local Seafood',
                    'Authentic Filipino Dishes',
                    'Ocean View Seating',
                    'Breakfast Buffet Daily',
                    'Family Friendly Tables',
                    'Indoor Air Conditioning',
                    'Free Wi-Fi',
                    'Table Service',
                ],
                'menu' => [
                    ['name' => 'Grilled Siquijor Catch', 'price' => '₱680', 'description' => 'Fresh local fish grilled with garlic butter, lemon, and native herbs.'],
                    ['name' => 'Sinigang na Hipon', 'price' => '₱520', 'description' => 'Tamarind sour soup with fresh prawns, radish, and local greens.'],
                    ['name' => 'Classic Pork Belly Adobo', 'price' => '₱460', 'description' => 'Slow cooked pork in native vinegar, soy sauce, and cracked garlic.'],
                    ['name' => 'Sunscape Seafood Kinilaw', 'price' => '₱420', 'description' => 'Fresh raw tuna marinated in calamansi juice, ginger, and coconut milk.'],
                    ['name' => 'Crispy Lechon Kawali', 'price' => '₱490', 'description' => 'Deep fried pork belly served with rich spiced liver sauce.'],
                    ['name' => 'Special Halo-Halo', 'price' => '₱220', 'description' => 'Shaved ice dessert with sweet beans, purple yam, milk, and flan.'],
                ],
                'guidelines' => [
                    'Hours: 6:00 AM to 10:00 PM every day.',
                    'Breakfast buffet served from 6:00 AM to 10:30 AM.',
                    'Dress code: Casual resort attire. Shirts and footwear required inside.',
                    'Walk-ins welcome. Table reservations suggested for dinner.',
                    'Payment: Cash, credit card, and room billing accepted.',
                ],
                'testimonials' => [
                    ['text' => 'The grilled fish and sinigang were so fresh and tasty. Great place to eat with the whole family.', 'name' => 'Maricel T.', 'stars' => 5],
                    ['text' => 'We enjoyed breakfast here every morning of our stay. The view of the water is lovely.', 'name' => 'Danilo V.', 'stars' => 5],
                    ['text' => 'Authentic Filipino dishes done right. Staff were very kind and polite.', 'name' => 'Grace L.', 'stars' => 5],
                ],
            ],
            'island-bar' => [
                'slug'        => 'island-bar',
                'name'        => 'The Island Bar',
                'tag'         => 'Light Bites & Cocktails',
                'badge_class' => 'deluxe',
                'hours'       => '8:00 AM - 12:00 AM',
                'subtitle'    => 'Poolside Lounge and Drinks by the Sea',
                'description' => 'Refreshing drinks and snacks by the sea. Unwind with chilled fruit juices, handcrafted island cocktails, and tasty light bites right next to the pool.',
                'location'    => 'Poolside Deck and Beachfront Path',
                'seating'     => 'Poolside Sun Loungers and Bar Stools',
                'price_range' => '₱200 - ₱500 per guest',
                'images'      => [
                    'images/island_bar.jpg',
                    'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1536935338788-846bb9981813?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1574096079513-d8259312b785?auto=format&fit=crop&w=1000&q=80',
                ],
                'highlights' => [
                    'Signature Tropical Cocktails',
                    'Sunset Viewpoint',
                    'Poolside Drink Service',
                    'Happy Hour Daily',
                    'Cold Local Beers',
                    'Light Snack Menu',
                    'Free Wi-Fi',
                    'Gentle Island Music',
                ],
                'menu' => [
                    ['name' => 'Siquijor Sunset Cocktail', 'price' => '₱320', 'description' => 'Dark rum mixed with fresh mango juice, grenadine, and lime.'],
                    ['name' => 'Sunscape Classic Mojito', 'price' => '₱290', 'description' => 'White rum, fresh garden mint, calamansi, and chilled soda water.'],
                    ['name' => 'Crispy Calamari Basket', 'price' => '₱360', 'description' => 'Golden fried squid rings served with lemon garlic dip.'],
                    ['name' => 'Loaded Beach Nachos', 'price' => '₱380', 'description' => 'Crisp corn chips topped with warm cheese, salsa, and mild peppers.'],
                    ['name' => 'Sweet Chili Chicken Wings', 'price' => '₱350', 'description' => 'Fried chicken wings glazed in sweet and tangy garlic sauce.'],
                    ['name' => 'Fresh Young Coconut Shake', 'price' => '₱180', 'description' => 'Fresh coconut water and young coconut meat blended with milk.'],
                ],
                'guidelines' => [
                    'Hours: 8:00 AM to 12:00 AM midnight daily.',
                    'Happy Hour specials available from 4:00 PM to 7:00 PM.',
                    'Dress code: Beach casual. Swimwear with dry cover-up is permitted.',
                    'Seating is first come, first served around the pool and bar deck.',
                    'Payment: Cash, credit card, and room billing accepted.',
                ],
                'testimonials' => [
                    ['text' => 'Watching the sunset with a cold mango drink here was the highlight of our afternoon.', 'name' => 'Arlene C.', 'stars' => 5],
                    ['text' => 'Great drinks and delicious nachos by the pool. Very relaxed atmosphere.', 'name' => 'Mark B.', 'stars' => 5],
                    ['text' => 'Friendly bartenders and nice music. A perfect spot to rest after swimming.', 'name' => 'Hannah K.', 'stars' => 5],
                ],
            ],
            'coast-grill' => [
                'slug'        => 'coast-grill',
                'name'        => 'Coast Grill Nights',
                'tag'         => 'Grilled Seafood & Filipino BBQ',
                'badge_class' => 'standard',
                'hours'       => '5:00 PM - 4:00 AM',
                'subtitle'    => 'Beach BBQ Nights Under the Stars',
                'description' => 'Open air dining under the stars. Enjoy freshly grilled seafood, local barbecue skewers, and open air dining right along the ocean breeze.',
                'location'    => 'Beachfront White Sand Area',
                'seating'     => 'Beachside Tables and Open Air Seating',
                'price_range' => '₱400 - ₱800 per guest',
                'images'      => [
                    'images/Beach-BBQ-9.jpg',
                    'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=1000&q=80',
                ],
                'highlights' => [
                    'Open Air Charcoal Grill',
                    'Fresh Red Snapper and Squid',
                    'Filipino Barbecue Skewers',
                    'Dining Under the Stars',
                    'Late Night Food Service',
                    'Beach Bonfire on Weekends',
                    'Free Wi-Fi',
                    'Ocean Breeze Seating',
                ],
                'menu' => [
                    ['name' => 'Whole Charcoal Red Snapper', 'price' => '₱750', 'description' => 'Whole fresh catch grilled over charcoal with lemongrass and sea salt.'],
                    ['name' => 'Pork BBQ Skewers (4 sticks)', 'price' => '₱340', 'description' => 'Tender pork skewers glazed with sweet savory Filipino barbecue sauce.'],
                    ['name' => 'Inihaw na Pusit', 'price' => '₱480', 'description' => 'Fresh whole squid stuffed with diced tomatoes and onions, charcoal grilled.'],
                    ['name' => 'Grilled Tiger Prawns', 'price' => '₱680', 'description' => 'Plump ocean prawns brushed with garlic butter and grilled to perfection.'],
                    ['name' => 'Chicken Inasal Quarter', 'price' => '₱420', 'description' => 'Chicken marinated in calamansi, ginger, and annatto oil, flame grilled.'],
                    ['name' => 'Grilled Sweet Corn and Rice', 'price' => '₱160', 'description' => 'Charred sweet corn on the cob served with warm fragrant garlic rice.'],
                ],
                'guidelines' => [
                    'Hours: 5:00 PM to 4:00 AM daily for dinner and late night cravings.',
                    'Peak grilling hours run from 6:00 PM to 11:00 PM.',
                    'Late night barbecue and drinks served until 4:00 AM.',
                    'Dress code: Relaxed island casual. Barefoot on the sand is welcome.',
                    'Reservations: Suggested for front-row ocean tables.',
                ],
                'testimonials' => [
                    ['text' => 'Eating grilled seafood with our feet in the sand under the stars was wonderful.', 'name' => 'Rodel F.', 'stars' => 5],
                    ['text' => 'The pork skewers and stuffed squid were cooked just right. Great late night option.', 'name' => 'Jasmine S.', 'stars' => 5],
                    ['text' => 'Loved the bonfire and the ocean breeze. A must visit when you stay at the resort.', 'name' => 'Chris T.', 'stars' => 5],
                ],
            ],
        ];
    }
}
