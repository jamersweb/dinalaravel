<template lang="">
    <div style="overflow:hidden;">
        <Header :detail="'RECOMENDED LIST OF'" :title="'FOODS'" :discription="'In life, our overall wellbeing is dependent on two things:'" />
        <div class="row" style="background-color:#EEEAEA;">
            <div class="col-12 px-4 col-sm-11 col-md-10 py-4 my-4 mx-auto position-relative" style="overflow-x:auto;background-color:white;">
                <table class="w-100">
                    <tr style="background-color:#c4c4c4">
                        <th>PROTEIN</th>
                        <th>FATS</th>
                        <th style="color:#fc3a3a;">REFRIGERATE CARBS FOR 3 HOURS EAT COLD FOR FAT LOSS & HORMONAL IMBALANCE</th>
                        <th>VEGGIES(CARBS)</th>
                        <th>FRUIT(CARBS)</th>
                        <th>SPICES</th>
                        <th>MILK</th>
                    </tr>
                    <tr>
                        <td>Protein bread</td>
                        <td>Keto bread</td>
                        <td>Rice brown, white,wild & black</td>
                        <td>Spinach</td>
                        <td>Berries</td>
                        <td>Ginger</td>
                        <td>Almond milk</td>
                    </tr>
                    <tr class="odd">
                        <td>Chicken breast</td>
                        <td>Salmon,steak,beef smoked salmon</td>
                        <td>Quinoa</td>
                        <td>Asparagus</td>
                        <td>Pineapple</td>
                        <td>Turmeric with black pepper</td>
                        <td>Coconut milk</td>
                    </tr>
                    <tr>
                        <td>Hammour</td>
                        <td>Walnuts</td>
                        <td>Couscous</td>
                        <td>Broccoli</td>
                        <td>Grapefruit</td>
                        <td>Cumin</td>
                        <td></td>
                    </tr>
                    <tr class="odd">
                        <td>Cod</td>
                        <td>Almonds</td>
                        <td>Freekeh</td>
                        <td>Bok choy</td>
                        <td>Fig</td>
                        <td>Garlic</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Red Snapper</td>
                        <td>Brazil nuts</td>
                        <td>Rice cakes</td>
                        <td>Hindbeh (dandelions)</td>
                        <td>Pruns</td>
                        <td>Basil</td>
                        <td></td>
                    </tr>
                    <tr class="odd">
                        <td>Mussels</td>
                        <td>Olive oil</td>
                        <td>Sweet potato</td>
                        <td>Onions</td>
                        <td>Apricot</td>
                        <td>Rosemary</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Steak filet mignon (high fat)</td>
                        <td>Coconut oil</td>
                        <td></td>
                        <td>Seaweed</td>
                        <td>Watermelon</td>
                        <td>Himalyan Salt</td>
                        <td></td>
                    </tr>
                    <tr class="odd">
                        <td>Shrimp</td>
                        <td>Eggs (yellow part)</td>
                        <td></td>
                        <td>Cauliflower</td>
                        <td>Banana</td>
                        <td>Curry</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Lamb</td>
                        <td>Sardines</td>
                        <td></td>
                        <td>Artichoke</td>
                        <td>Apple</td>
                        <td>Cayene pepper</td>
                        <td></td>
                    </tr>
                    <tr class="odd">
                        <td>Salmon (high fat)</td>
                        <td>Pumpkin seeds</td>
                        <td></td>
                        <td></td>
                        <td>Lemon</td>
                        <td>Apple cider vinegar</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Tuna</td>
                        <td>Flex seeds</td>
                        <td></td>
                        <td></td>
                        <td>Dates</td>
                        <td>Cinnamon</td>
                        <td></td>
                    </tr>
                    <tr class="odd">
                        <td>Egg whites</td>
                        <td>Chia seed</td>
                        <td></td>
                        <td></td>
                        <td>Honey</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Sardines (high in fat)</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </div>
            <div class="col-12 px-4 px-sm-1 col-sm-11 col-md-10 py-4 my-4 mx-auto position-relative">
                <p class="mb-0 text-center f-40 fw-bold text-dark">YOU CAN USE BLOOD TYPE DIET LIST</p>
                <p class="mb-0 text-center f-40 fw-bold" style="color:#FC9C88;">AS A REFRENCE</p>
                <p class="mb-0 mt-3 text-center f-40 fw-bold text-dark">BELOW ARE THE DIFFERENT BLOOD</p>
                <p class="mb-0 text-center f-40 fw-bold text-dark">TYPE GROUPS</p>
                <p class="mb-0 text-center f-40 fw-bold" style="color:#FC9C88;">A , AB , B , O</p>
            </div>
            <div  class="col-12 px-4 col-sm-11 col-md-10 py-4 my-4 mx-auto position-relative" style="overflow-x:auto;background-color:white;">
                <p class="mb-0 f-40 fw-bold text-dark">BLOOD TYPE A DIET</p>
                <table class="w-100">
                    <tr class="odd">
                        <th></th>
                        <th style="background-color:#f3f7ec;color:#798e4c;">Most Beneficial</th>
                        <th style="background-color:#f3faff;color:#7395bc;">Allowed</th>
                        <th style="background-color:#fdeaed;color:#c94445;">Not Allowed</th>
                    </tr>
                    <tr>
                        <td id="column1"></td>
                    </tr>
                </table>
            </div>
        </div>
        <Footer />
    </div>
</template>
<script>
import Header from './components/header.vue';
import Footer from './components/footer.vue';
export default {
    components: { Header, Footer },
}
</script>
<style scoped>
th {
    width: 200px;
    font-size: 15px;
    text-align: center;
}

td {
    font-size: 13px;
    padding-left: 5px;
    padding-right: 5px;
}

table,
td {
    border: 1px solid #C5C5C5;
}

.odd {
    background-color: #edebeb;
}

table tr#column1 {
    background-color: red;
}
</style>
